<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\GeminiController;
use App\Models\Product;
use Illuminate\Http\Request;

class TestFallbackRecommendations extends Command
{
    protected $signature = 'test:fallback-recommendations {product_id=1}';
    protected $description = 'Test fallback recommendations for different contexts';

    public function handle()
    {
        $productId = $this->argument('product_id');
        $this->info("Testing fallback recommendations for product ID: {$productId}");
        
        // Get the product
        $product = Product::find($productId);
        if (!$product) {
            $this->error("Product with ID {$productId} not found");
            return Command::FAILURE;
        }
        
        $this->info("Product: {$product->name} (Category: {$product->category})");
        
        // Get all products
        $allProducts = Product::select(['id', 'name', 'description', 'price', 'image', 'category', 'tags', 'rating'])
            ->inStock()
            ->get()
            ->toArray();
        
        $this->info("Total products available: " . count($allProducts));
        
        // Count products in same category
        $categoryProducts = collect($allProducts)
            ->where('category', $product->category)
            ->where('id', '!=', $productId)
            ->count();
        
        $this->info("Products in same category: {$categoryProducts}");
        
        // Create controller instance
        $controller = new GeminiController();
        
        // Test 1: Product view context (should show only category products)
        $this->line('');
        $this->info('=== TEST 1: Product View Context (Should show category products) ===');
        
        $request = new Request();
        $request->merge([
            'product_id' => $productId,
            'context' => 'product_view',
            'cart_items' => []
        ]);
        
        $response = $controller->recommendations($request);
        $responseData = $response->getData();
        
        $this->info("Algorithm used: " . $responseData->algorithm);
        $this->info("Recommendations count: " . count($responseData->recommendations));
        
        foreach ($responseData->recommendations as $index => $rec) {
            $this->line("  " . ($index + 1) . ". {$rec->name} (Category: {$rec->category})");
        }
        
        // Test 2: Homepage context (should show mixed recommendations)
        $this->line('');
        $this->info('=== TEST 2: Homepage Context (Should show mixed recommendations) ===');
        
        $request = new Request();
        $request->merge([
            'product_id' => $productId,
            'context' => 'homepage',
            'cart_items' => []
        ]);
        
        $response = $controller->recommendations($request);
        $responseData = $response->getData();
        
        $this->info("Algorithm used: " . $responseData->algorithm);
        $this->info("Recommendations count: " . count($responseData->recommendations));
        
        foreach ($responseData->recommendations as $index => $rec) {
            $this->line("  " . ($index + 1) . ". {$rec->name} (Category: {$rec->category})");
        }
        
        // Test 3: Cart context (should show mixed recommendations)
        $this->line('');
        $this->info('=== TEST 3: Cart Context (Should show mixed recommendations) ===');
        
        $request = new Request();
        $request->merge([
            'product_id' => $productId,
            'context' => 'cart',
            'cart_items' => [
                ['id' => $productId, 'name' => $product->name, 'quantity' => 1, 'price' => $product->price]
            ]
        ]);
        
        $response = $controller->recommendations($request);
        $responseData = $response->getData();
        
        $this->info("Algorithm used: " . $responseData->algorithm);
        $this->info("Recommendations count: " . count($responseData->recommendations));
        
        foreach ($responseData->recommendations as $index => $rec) {
            $this->line("  " . ($index + 1) . ". {$rec->name} (Category: {$rec->category})");
        }
        
        return Command::SUCCESS;
    }
}
