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
            
            // Prepare order data for token generation
            $orderData = [
                'user_id' => auth()->id(),
                'items' => $cartItems,
                'total' => $totalAmount
            ];
            
            // Reserve stock for the order
            $orderToken = $request->input('order_token', $this->orderService->generateOrderToken($orderData));
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
            'payment_intent_data' => [
                'metadata' => [
                    'order_token' => $orderToken,
                    'user_id' => auth()->id(),
                ]
            ],
            'metadata' => [
                'user_id' => auth()->id(),
                'order_token' => $orderToken,
                'idempotency_key' => $idempotencyKey,
                'item_count' => count($cartItems),
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
                    'status' => 'processing', // Changed from 'completed' to 'processing' (valid enum value)
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
                
                // Redirect to cart page with success message
                return redirect()->route('cart.index')->with([
                    'payment_success' => true,
                    'order_number' => $order->order_number,
                    'session_id' => $sessionId,
                    'success_message' => 'Payment successful! Your order #' . $order->order_number . ' has been confirmed.'
                ]);
                
            } catch (\Exception $e) {
                Log::error('Order creation error: ' . $e->getMessage());
                $request->session()->flash('payment_error', 'Order creation failed: ' . $e->getMessage());
                return redirect()->route('cart.index');
            }
        }

        // If no session ID, redirect to cart
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
    
    /**
     * Handle Stripe webhook events.
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('stripe.webhook_secret');
        
        // Log webhook attempt for debugging
        Log::info('Stripe webhook received', [
            'payload_size' => strlen($payload),
            'has_signature' => !empty($sigHeader),
            'has_secret' => !empty($endpointSecret),
            'headers' => $request->headers->all()
        ]);
        
        // Check if webhook secret is configured
        if (empty($endpointSecret) || $endpointSecret === 'whsec_your_actual_stripe_webhook_secret_here') {
            Log::warning('Stripe webhook secret not properly configured', [
                'current_secret' => $endpointSecret
            ]);
            return response('Webhook secret not configured', 500);
        }
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid payload in Stripe webhook: ' . $e->getMessage());
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Invalid signature in Stripe webhook: ' . $e->getMessage());
            return response('Invalid signature', 400);
        }
        
        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;
            default:
                Log::info('Unhandled Stripe webhook event type: ' . $event->type);
        }
        
        return response('Webhook handled', 200);
    }
    
    /**
     * Handle checkout session completed event.
     * 
     * @param object $session
     * @return void
     */
    private function handleCheckoutSessionCompleted($session)
    {
        Log::info('Checkout session completed', [
            'session_id' => $session->id,
            'payment_status' => $session->payment_status,
            'customer_email' => $session->customer_details->email ?? null,
            'metadata' => $session->metadata ?? []
        ]);
        
        try {
            DB::beginTransaction();
            
            // Get order token from metadata
            $orderToken = $session->metadata->order_token ?? null;
            $userId = $session->metadata->user_id ?? null;
            
            if (!$orderToken || !$userId) {
                Log::error('Missing order token or user ID in checkout session', [
                    'session_id' => $session->id,
                    'order_token' => $orderToken,
                    'user_id' => $userId
                ]);
                DB::rollBack();
                return;
            }
            
            // Check if order already exists (idempotency)
            $existingOrder = \App\Models\Order::where('order_token', $orderToken)->first();
            if ($existingOrder) {
                Log::info('Order already exists for token', [
                    'order_token' => $orderToken,
                    'order_id' => $existingOrder->id
                ]);
                DB::commit();
                return;
            }
            
            // Get cart items from the session (we need to reconstruct this)
            $cartItems = $this->reconstructCartItemsFromSession($session);
            
            if (empty($cartItems)) {
                Log::error('No cart items found in session', [
                    'session_id' => $session->id
                ]);
                DB::rollBack();
                return;
            }
            
            // Calculate totals
            $subtotal = $session->amount_subtotal / 100; // Convert from cents
            $tax = $session->total_details->amount_tax / 100;
            $shipping = $session->total_details->amount_shipping / 100;
            $total = $session->amount_total / 100;
            
            // Prepare order data
            $orderData = [
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'total' => $total,
                'stripe_session_id' => $session->id,
                'status' => 'processing',
                'shipping_address' => json_encode($session->shipping_details ?? []),
                'billing_address' => json_encode($session->customer_details ?? []),
                'items' => $cartItems
            ];
            
            // Create order with idempotency protection
            $order = $this->orderService->createOrder($orderData, $orderToken);
            
            Log::info('Order created successfully via webhook', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_token' => $orderToken,
                'session_id' => $session->id
            ]);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating order in webhook: ' . $e->getMessage(), [
                'session_id' => $session->id,
                'order_token' => $orderToken ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Handle payment intent succeeded event.
     * 
     * @param object $paymentIntent
     * @return void
     */
    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        Log::info('Payment intent succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'metadata' => $paymentIntent->metadata ?? []
        ]);
        
        try {
            DB::beginTransaction();
            
            // Get order token from metadata
            $orderToken = $paymentIntent->metadata->order_token ?? null;
            
            if ($orderToken) {
                // Find the order
                $order = \App\Models\Order::where('order_token', $orderToken)->first();
                
                if ($order) {
                    // Update order status to completed
                    $order->update(['status' => 'completed']);
                    
                    // Confirm stock reservations (convert to actual stock deduction)
                    $this->stockReservationService->confirmReservation($orderToken);
                    
                    // Clear the cart for the user
                    $this->cartService->clearCart();
                    
                    Log::info('Order completed and stock confirmed via webhook', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_token' => $orderToken,
                        'payment_intent_id' => $paymentIntent->id
                    ]);
                } else {
                    Log::warning('Order not found for token in payment intent webhook', [
                        'order_token' => $orderToken,
                        'payment_intent_id' => $paymentIntent->id
                    ]);
                }
            } else {
                Log::warning('No order token found in payment intent metadata', [
                    'payment_intent_id' => $paymentIntent->id
                ]);
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing payment intent success: ' . $e->getMessage(), [
                'payment_intent_id' => $paymentIntent->id,
                'order_token' => $orderToken ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Handle payment intent failed event.
     * 
     * @param object $paymentIntent
     * @return void
     */
    private function handlePaymentIntentFailed($paymentIntent)
    {
        Log::info('Payment intent failed', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'last_payment_error' => $paymentIntent->last_payment_error ?? null,
            'metadata' => $paymentIntent->metadata ?? []
        ]);
        
        try {
            // Get order token from metadata
            $orderToken = $paymentIntent->metadata->order_token ?? null;
            
            if ($orderToken) {
                // Cancel stock reservations if payment fails
                $this->stockReservationService->cancelReservationByToken($orderToken);
                
                Log::info('Stock reservations cancelled due to payment failure', [
                    'order_token' => $orderToken,
                    'payment_intent_id' => $paymentIntent->id
                ]);
            } else {
                Log::warning('No order token found in failed payment intent metadata', [
                    'payment_intent_id' => $paymentIntent->id
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error cancelling stock reservations: ' . $e->getMessage(), [
                'order_token' => $orderToken ?? null,
                'payment_intent_id' => $paymentIntent->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Reconstruct cart items from Stripe session line items.
     * 
     * @param object $session
     * @return array
     */
    private function reconstructCartItemsFromSession($session)
    {
        $cartItems = [];
        
        try {
            // Retrieve the session with line items expanded
            \Stripe\Stripe::setApiKey(config('stripe.sk'));
            $sessionWithLineItems = \Stripe\Checkout\Session::retrieve($session->id, [
                'expand' => ['line_items.data.price.product']
            ]);
            
            foreach ($sessionWithLineItems->line_items->data as $lineItem) {
                // Skip shipping and tax items
                if (in_array(strtolower($lineItem->description ?? ''), ['shipping', 'tax'])) {
                    continue;
                }
                
                $product = $lineItem->price->product;
                $cartItems[] = [
                    'id' => $product->id ?? uniqid(),
                    'product_id' => $product->id ?? uniqid(),
                    'name' => $product->name ?? $lineItem->description ?? 'Unknown Product',
                    'description' => $product->description ?? '',
                    'price' => $lineItem->price->unit_amount / 100, // Convert from cents
                    'quantity' => $lineItem->quantity,
                    'subtotal' => ($lineItem->price->unit_amount / 100) * $lineItem->quantity,
                    'image' => $product->images[0] ?? null
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Error reconstructing cart items from session: ' . $e->getMessage(), [
                'session_id' => $session->id
            ]);
        }
        
        return $cartItems;
    }
}
