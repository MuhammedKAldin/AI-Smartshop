<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CartService;
use App\Services\GeminiExportService;
use App\Services\OrderService;
use App\Services\StockReservationService;
use App\Models\Order;
use App\Models\OrderItem;

class StripeController extends Controller
{
    protected $cartService;
    protected $orderService;
    protected $stockReservationService;

    public function __construct(CartService $cartService, OrderService $orderService, StockReservationService $stockReservationService)
    {
        $this->cartService = $cartService;
        $this->orderService = $orderService;
        $this->stockReservationService = $stockReservationService;
    }
    /**
     * Display the Stripe test page.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('index');
    }

    /**
     * Create a Stripe checkout session.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkout(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Generate idempotency key for this payment request
            $idempotencyKey = $this->generateIdempotencyKey($request);
            
            // Log the request for debugging
            \Log::info('Stripe checkout request:', [
                'idempotency_key' => $idempotencyKey,
                'cart_items' => $request->input('cart_items', []),
                'shipping_info' => $request->input('shipping_info', []),
                'total_amount' => $request->input('total_amount', 0)
            ]);
            
            \Stripe\Stripe::setApiKey(config('stripe.sk'));
            
            // Get cart items from request
            $cartItems = $request->input('cart_items', []);
            $shippingInfo = $request->input('shipping_info', []);
            $totalAmount = $request->input('total_amount', 0);
            
            // Validate required fields
            if (empty($cartItems)) {
                DB::rollBack();
                return response()->json(['error' => 'Cart is empty'], 400);
            }
            
            if (empty($shippingInfo['email'])) {
                DB::rollBack();
                return response()->json(['error' => 'Email is required'], 400);
            }
            
            // Validate cart stock before processing payment
            $cartValidation = $this->stockReservationService->validateCartReservation($cartItems);
            
            if (!$cartValidation['can_reserve']) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Some items in your cart are no longer available',
                    'error_type' => 'stock_issue',
                    'unavailable_items' => $cartValidation['unavailable_items']
                ], 400);
            }
            
            // Reserve stock for the order
            $orderToken = $request->input('order_token', $this->generateOrderToken($request));
            $reservations = $this->stockReservationService->reserveCartStock(
                $cartItems,
                $orderToken,
                auth()->id(),
                session()->getId()
            );
            
            // Validate cart items have required fields
            foreach ($cartItems as $index => $item) {
                if (empty($item['name'])) {
                    DB::rollBack();
                    return response()->json(['error' => "Product name is required for item {$index}"], 400);
                }
                if (empty($item['price']) || $item['price'] <= 0) {
                    DB::rollBack();
                    return response()->json(['error' => "Valid price is required for item {$index}"], 400);
                }
                if (empty($item['quantity']) || $item['quantity'] <= 0) {
                    DB::rollBack();
                    return response()->json(['error' => "Valid quantity is required for item {$index}"], 400);
                }
            }
        
        // Convert cart items to Stripe line items
        $lineItems = [];
        foreach ($cartItems as $item) {
            $productData = [
                'name' => $item['name'],
            ];
            
            // Only add description if it exists and is not empty
            if (!empty($item['description'])) {
                $productData['description'] = $item['description'];
            }
            
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => $productData,
                    'unit_amount' => round($item['price'] * 100), // Convert to cents
                ],
                'quantity' => $item['quantity'],
            ];
        }
        
        // Add shipping if applicable
        if ($totalAmount < 50) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Shipping',
                    ],
                    'unit_amount' => 999, // $9.99 in cents
                ],
                'quantity' => 1,
            ];
        }
        
        // Add tax
        $taxAmount = $totalAmount * 0.08;
        if ($taxAmount > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Tax',
                    ],
                    'unit_amount' => round($taxAmount * 100),
                ],
                'quantity' => 1,
            ];
        }
        
        $session = \Stripe\Checkout\Session::create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.index'),
            'customer_email' => $shippingInfo['email'] ?? null,
            'shipping_address_collection' => [
                'allowed_countries' => ['US', 'CA'],
            ],
            'metadata' => [
                'user_id' => auth()->id(),
                'cart_items' => json_encode($cartItems),
                'idempotency_key' => $idempotencyKey,
            ],
        ], [
            'idempotency_key' => $idempotencyKey
        ]);
        
        DB::commit();
        return response()->json(['url' => $session->url]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Cancel any stock reservations if payment fails
            if (isset($orderToken)) {
                $this->stockReservationService->cancelReservationByToken($orderToken);
            }
            
            \Log::error('Stripe checkout error: ' . $e->getMessage());
            return response()->json(['error' => 'Payment processing failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handle successful payment redirect from Stripe.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        
        if ($sessionId) {
            try {
                // Retrieve Stripe session to get payment details
                \Stripe\Stripe::setApiKey(config('stripe.sk'));
                $stripeSession = \Stripe\Checkout\Session::retrieve($sessionId);
                
                // Get cart items for order creation
                $cartItems = $this->cartService->getCartItems();
                $cartTotal = $this->cartService->getCartTotal();
                
                // Prepare order data
                $orderData = [
                    'user_id' => auth()->id(),
                    'subtotal' => $cartTotal,
                    'tax' => $cartTotal * 0.08, // 8% tax
                    'shipping' => 9.99, // Fixed shipping
                    'total' => $cartTotal + ($cartTotal * 0.08) + 9.99,
                    'stripe_session_id' => $sessionId,
                    'status' => 'completed',
                    'shipping_address' => json_encode($stripeSession->shipping_details ?? []),
                    'billing_address' => json_encode($stripeSession->customer_details ?? []),
                    'items' => $cartItems
                ];
                
                // Generate order token for idempotency
                $orderToken = $this->orderService->generateOrderToken($orderData);
                
                // Create order with idempotency protection
                $order = $this->orderService->createOrder($orderData, $orderToken);
                
                // Confirm stock reservations (convert to actual stock deduction)
                $this->stockReservationService->confirmReservation($orderToken);
                
                // Clear the cart after successful order creation
                $this->cartService->clearCart();
                
                $request->session()->flash('payment_success', true);
                $request->session()->flash('session_id', $sessionId);
                $request->session()->flash('order_number', $order->order_number);
                
            } catch (\Exception $e) {
                Log::error('Order creation error: ' . $e->getMessage());
                $request->session()->flash('payment_error', 'Order creation failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('cart.index');
    }
    
    /**
     * Generate idempotency key for payment requests.
     * 
     * @param Request $request
     * @return string
     */
    private function generateIdempotencyKey(Request $request): string
    {
        $data = [
            'user_id' => auth()->id(),
            'cart_items' => $request->input('cart_items', []),
            'shipping_info' => $request->input('shipping_info', []),
            'total_amount' => $request->input('total_amount', 0),
            'timestamp' => now()->format('Y-m-d H:i') // Round to minute for idempotency
        ];
        
        return 'stripe_checkout_' . hash('sha256', json_encode($data));
    }
}
