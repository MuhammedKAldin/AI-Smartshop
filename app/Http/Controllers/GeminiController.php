<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\GeminiExportService;
use App\Models\Product;

class GeminiController extends Controller
{
    // Simple GET endpoint: /ai?prompt=...
    public function ai(Request $request)
    {
        try {
            $userPrompt = $request->query('prompt');
            $debug = $request->query('debug', false);

            if (!$userPrompt) {
                return response()->json([
                    'error' => 'Prompt is required'
                ], 400);
            }

            $apiKey = config('ai.gemini.api_key');
            if (!$apiKey) {
                return response()->json([
                    'error' => 'Missing GEMINI_API_KEY in environment'
                ], 500);
            }

            $endpoint = config('ai.model.endpoint_for_model')(null);

            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [ ['text' => $userPrompt] ],
                    ],
                ],
            ];

            $response = Http::asJson()->post($endpoint.'?key='.$apiKey, $payload);

            $responseData = $response->json();
            
            if (!$debug) {
                // Use the centralized response parsing from ai.php configuration
                return config('ai.model.extract_response_content')($responseData);
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to get response AI',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AI-powered product recommendations using Google Gemini.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recommendations(Request $request)
    {
        try {
            $userId = $request->input('user_id') ?: auth()->id();
            $productId = $request->input('product_id');
            $cartItems = $request->input('cart_items', []);
            $context = $request->input('context', 'homepage'); // homepage, product_view, cart
            
            // Check if user is logged in
            $isLoggedIn = !empty($userId);
            
            // Get products from database with optimized query
            $allProducts = Product::select(['id', 'name', 'description', 'price', 'image', 'category', 'tags', 'rating'])
                ->inStock()
                ->get()
                ->toArray();
            
            // If user is not logged in, use fallback mechanism
            if (!$isLoggedIn) {
                return $this->getFallbackRecommendations($allProducts, $productId, $cartItems, $context);
            }
            
            // Build comprehensive AI context for logged-in users
            $aiContext = $this->buildAIRecommendationContext($userId, $productId, $cartItems, $context, $allProducts);
            
            // Prepare request data for export
            $requestData = [
                'user_id' => $userId,
                'product_id' => $productId,
                'cart_items' => $cartItems,
                'context' => $context,
                'total_products_available' => count($allProducts),
                'user_profile' => $this->getUserProfile($userId)
            ];
            
            // Get AI recommendations
            $apiKey = config('ai.gemini.api_key');
            if (!$apiKey) {
                // Export fallback call
                GeminiExportService::exportCall(
                    $requestData,
                    $aiContext,
                    ['fallback_used' => true, 'reason' => 'no_api_key'],
                    $context
                );
                
                // Fallback to algorithm-based recommendations
                return $this->getFallbackRecommendations($allProducts, $productId, $cartItems, $context);
            }
            
            $endpoint = config('ai.model.endpoint_for_model')(null);
            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $aiContext]],
                    ],
                ],
            ];
            
            // Make API call
            $response = Http::asJson()->post($endpoint.'?key='.$apiKey, $payload);
            $responseData = $response->json();
            
            // Extract AI response
            $aiResponse = config('ai.model.extract_response_content')($responseData);
            
            // Parse AI response to get product IDs
            $recommendedIds = [];
            if (is_string($aiResponse)) {
                // Try to extract JSON array from response
                if (preg_match('/\[(\d+(?:,\s*\d+)*)\]/', $aiResponse, $matches)) {
                    $recommendedIds = array_map('intval', explode(',', $matches[1]));
                }
            }
            
            // Prepare response data for export
            $exportResponseData = [
                'raw_response' => $responseData,
                'extracted_response' => $aiResponse,
                'parsed_product_ids' => $recommendedIds,
                'success' => !empty($recommendedIds)
            ];
            
            // Export the AI call
            $exportPath = GeminiExportService::exportCall(
                $requestData,
                $aiContext,
                $exportResponseData,
                $context
            );
            
            // If AI didn't return valid IDs, use fallback
            if (empty($recommendedIds)) {
                Log::warning('AI returned no valid product IDs, using fallback', [
                    'ai_response' => $aiResponse,
                    'export_path' => $exportPath
                ]);
                return $this->getFallbackRecommendations($allProducts, $productId, $cartItems, $context);
            }
            
            // Get recommended products
            $recommendations = collect($allProducts)->whereIn('id', $recommendedIds)->take(4)->values()->toArray();
            
            // Track recommendation generation for analytics
            $this->trackRecommendationGeneration($userId, $recommendations, $context, 'ai');
            
            return response()->json([
                'recommendations' => $recommendations,
                'source' => 'ai',
                'personalized' => true,
                'export_path' => $exportPath
            ]);
            
        } catch (\Exception $e) {
            Log::error('AI Recommendations Error: ' . $e->getMessage());
            
            // Export error call
            $requestData = [
                'user_id' => $request->input('user_id'),
                'product_id' => $request->input('product_id'),
                'cart_items' => $request->input('cart_items', []),
                'context' => $request->input('context', 'homepage'),
                'error' => $e->getMessage()
            ];
            
            GeminiExportService::exportCall(
                $requestData,
                'Error occurred during AI recommendation generation',
                ['error' => $e->getMessage(), 'fallback_used' => true],
                $request->input('context', 'homepage')
            );
            
            // Fallback to simple recommendations
            return $this->getFallbackRecommendations([], $request->input('product_id'), $request->input('cart_items', []), $request->input('context', 'homepage'));
        }
    }
    
    /**
     * Build comprehensive AI recommendation context for logged-in users.
     * 
     * @param int $userId
     * @param int|null $productId
     * @param array $cartItems
     * @param string $context
     * @param array $allProducts
     * @return string
     */
    private function buildAIRecommendationContext($userId, $productId, $cartItems, $context, $allProducts)
    {
        // Get user profile with order history and browsing data
        $userProfile = $this->getUserProfile($userId);
        
        $aiContext = "Based on the following comprehensive user information, recommend 4 personalized products:\n\n";
        
        // User Profile Context
        $aiContext .= "USER PROFILE:\n";
        $aiContext .= "User ID: {$userId}\n";
        $aiContext .= "Preferred Categories: " . implode(', ', $userProfile['preferred_categories']) . "\n";
        $aiContext .= "Average Purchase Price: $" . number_format($userProfile['avg_purchase_price'], 2) . "\n";
        $aiContext .= "Total Orders: {$userProfile['total_orders']}\n";
        $aiContext .= "Recent Browsing: " . implode(', ', $userProfile['recent_views']) . "\n\n";
        
        // Current Context
        if ($productId) {
            $currentProduct = collect($allProducts)->firstWhere('id', $productId);
            if ($currentProduct) {
                $aiContext .= "CURRENT PRODUCT:\n";
                $aiContext .= "Name: {$currentProduct['name']}\n";
                $aiContext .= "Description: {$currentProduct['description']}\n";
                $aiContext .= "Category: {$currentProduct['category']}\n";
                $aiContext .= "Price: $" . number_format($currentProduct['price'], 2) . "\n";
                $aiContext .= "Tags: " . implode(', ', $currentProduct['tags']) . "\n\n";
            }
        }
        
        // Cart Context
        if (!empty($cartItems)) {
            $aiContext .= "CART CONTENTS:\n";
            foreach ($cartItems as $item) {
                $aiContext .= "- {$item['name']} (Qty: {$item['quantity']}, Price: $" . number_format($item['price'], 2) . ")\n";
            }
            $aiContext .= "\n";
        }
        
        // Purchase History Context
        if (!empty($userProfile['purchase_history'])) {
            $aiContext .= "PURCHASE HISTORY:\n";
            foreach ($userProfile['purchase_history'] as $purchase) {
                $aiContext .= "- {$purchase['name']} (Category: {$purchase['category']}, Price: $" . number_format($purchase['price'], 2) . ")\n";
            }
            $aiContext .= "\n";
        }
        
        // Similar User Patterns
        if (!empty($userProfile['similar_user_patterns'])) {
            $aiContext .= "SIMILAR USER PATTERNS:\n";
            $aiContext .= "Users with similar preferences also bought: " . implode(', ', $userProfile['similar_user_patterns']) . "\n\n";
        }
        
        // Available Products
        $aiContext .= "AVAILABLE PRODUCTS:\n";
        foreach ($allProducts as $product) {
            $aiContext .= "- {$product['name']} (ID: {$product['id']}) - {$product['description']} - Category: {$product['category']} - Price: $" . number_format($product['price'], 2) . " - Tags: " . implode(', ', $product['tags']) . "\n";
        }
        
        $aiContext .= "\nRecommendation Context: {$context}\n";
        $aiContext .= "Please recommend 4 products that would be most relevant to this user based on their profile, preferences, and current context. Return only the product IDs in a JSON array format like [1, 2, 3, 4].";
        
        return $aiContext;
    }
    
    /**
     * Get comprehensive user profile including order history and browsing data.
     * 
     * @param int $userId
     * @return array
     */
    private function getUserProfile($userId)
    {
        // Get user interactions (browsing data)
        $interactions = \App\Models\UserInteraction::where('user_id', $userId)
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get order history
        $orders = \App\Models\Order::where('user_id', $userId)
            ->with(['orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Analyze preferred categories
        $categoryCounts = [];
        $totalSpent = 0;
        $totalOrders = $orders->count();
        
        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $category = $item->product->category;
                $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
                $totalSpent += $item->price * $item->quantity;
            }
        }
        
        // Get recent views
        $recentViews = $interactions->where('interaction_type', 'view')
            ->take(10)
            ->pluck('product.name')
            ->toArray();
        
        // Get purchase history
        $purchaseHistory = [];
        foreach ($orders->take(5) as $order) {
            foreach ($order->orderItems as $item) {
                $purchaseHistory[] = [
                    'name' => $item->product->name,
                    'category' => $item->product->category,
                    'price' => $item->price
                ];
            }
        }
        
        // Get similar user patterns (users who bought similar products)
        $similarUserPatterns = $this->getSimilarUserPatterns($userId);
        
        return [
            'preferred_categories' => array_keys($categoryCounts),
            'avg_purchase_price' => $totalOrders > 0 ? $totalSpent / $totalOrders : 0,
            'total_orders' => $totalOrders,
            'recent_views' => $recentViews,
            'purchase_history' => $purchaseHistory,
            'similar_user_patterns' => $similarUserPatterns
        ];
    }
    
    /**
     * Get similar user patterns based on purchase history.
     * 
     * @param int $userId
     * @return array
     */
    private function getSimilarUserPatterns($userId)
    {
        // Get current user's purchased product categories
        $userCategories = \App\Models\Order::where('user_id', $userId)
            ->with(['orderItems.product'])
            ->get()
            ->flatMap(function($order) {
                return $order->orderItems->pluck('product.category');
            })
            ->unique()
            ->toArray();
        
        if (empty($userCategories)) {
            return [];
        }
        
        // Find users who bought products in similar categories
        $similarUsers = \App\Models\Order::where('user_id', '!=', $userId)
            ->with(['orderItems.product'])
            ->get()
            ->filter(function($order) use ($userCategories) {
                $orderCategories = $order->orderItems->pluck('product.category')->unique()->toArray();
                return !empty(array_intersect($userCategories, $orderCategories));
            })
            ->flatMap(function($order) {
                return $order->orderItems->pluck('product.name');
            })
            ->unique()
            ->take(5)
            ->toArray();
        
        return $similarUsers;
    }
    
    /**
     * Track recommendation generation for analytics.
     * 
     * @param int $userId
     * @param array $recommendations
     * @param string $context
     * @param string $source
     * @return void
     */
    private function trackRecommendationGeneration($userId, $recommendations, $context, $source)
    {
        try {
            \App\Models\AiRecommendation::create([
                'user_id' => $userId,
                'recommendations_json' => $recommendations,
                'context' => $context,
                'created_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track recommendation generation: ' . $e->getMessage());
        }
    }
    
    /**
     * Get fallback recommendations when AI is unavailable.
     * Uses simple algorithms based on category, price, and popularity.
     * 
     * @param array $allProducts
     * @param int|null $productId
     * @param array $cartItems
     * @param string $context
     * @return array
     */
    private function getFallbackRecommendations($allProducts, $productId = null, $cartItems = [], $context = 'homepage')
    {
        // Simple fallback algorithm
        $recommendations = [];
        $algorithm = 'random';
        
        if (empty($allProducts)) {
            // Get random products from database as fallback
            $recommendations = Product::inRandomOrder()->take(4)->get()->toArray();
            $algorithm = 'database_random';
        } else {
            // Get current product and its category
            $currentProduct = collect($allProducts)->firstWhere('id', $productId);
            $category = $currentProduct['category'] ?? null;
            
            // For product detail pages, prioritize category-based recommendations
            if ($context === 'product_view' && $category && $productId) {
                // Get all products from the same category (excluding current product)
                $categoryProducts = collect($allProducts)
                    ->where('category', $category)
                    ->where('id', '!=', $productId)
                    ->values()
                    ->toArray();
                
                if (count($categoryProducts) >= 4) {
                    // If we have enough products in the category, use only category products
                    $recommendations = collect($categoryProducts)
                        ->random(4)
                        ->values()
                        ->toArray();
                    $algorithm = 'category_random';
                } else {
                    // If not enough category products, use all available category products
                    $recommendations = $categoryProducts;
                    $algorithm = 'category_partial';
                }
            } else {
                // For other contexts (homepage, cart), use mixed approach
                if ($category) {
                    $recommendations = collect($allProducts)
                        ->where('category', $category)
                        ->where('id', '!=', $productId)
                        ->take(2)
                        ->values()
                        ->toArray();
                    $algorithm = 'category_based';
                }
            }
            
            // Fill remaining slots with random products (only if not product_view context)
            $remaining = 4 - count($recommendations);
            if ($remaining > 0 && $context !== 'product_view') {
                $randomProducts = collect($allProducts)
                    ->where('id', '!=', $productId)
                    ->random($remaining)
                    ->values()
                    ->toArray();
                $recommendations = array_merge($recommendations, $randomProducts);
                $algorithm = $algorithm === 'category_based' ? 'category_based_with_random' : 'random';
            }
        }
        
        // Export fallback recommendation data
        $requestData = [
            'product_id' => $productId,
            'cart_items' => $cartItems,
            'context' => $context,
            'total_products_available' => count($allProducts),
            'algorithm_used' => $algorithm
        ];
        
        $fallbackPrompt = "Fallback recommendation algorithm used: {$algorithm}. " .
                         "Context: {$context}. " .
                         "Product ID: " . ($productId ?? 'none') . ". " .
                         "Cart items: " . count($cartItems) . " items.";
        
        GeminiExportService::exportCall(
            $requestData,
            $fallbackPrompt,
            [
                'recommendations' => $recommendations,
                'algorithm' => $algorithm,
                'fallback_used' => true
            ],
            $context
        );
        
        return response()->json([
            'recommendations' => $recommendations,
            'source' => 'fallback',
            'personalized' => false,
            'algorithm' => $algorithm
        ]);
    }
    
    /**
     * Track user interaction for analytics and personalization.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trackInteraction(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $productId = $request->input('product_id');
            $interactionType = $request->input('interaction_type'); // view, add_to_cart, purchase
            
            if (!$userId || !$productId || !$interactionType) {
                return response()->json(['error' => 'Missing required parameters'], 400);
            }
            
            // Track the interaction
            \App\Models\UserInteraction::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'interaction_type' => $interactionType,
                'created_at' => now()
            ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Failed to track user interaction: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to track interaction'], 500);
        }
    }
}
