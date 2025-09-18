<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StripeController extends Controller
{
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
        \Stripe\Stripe::setApiKey(config('stripe.sk'));
        
        // Get cart items from request
        $cartItems = $request->input('cart_items', []);
        $shippingInfo = $request->input('shipping_info', []);
        $totalAmount = $request->input('total_amount', 0);
        
        // Convert cart items to Stripe line items
        $lineItems = [];
        foreach ($cartItems as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item['name'],
                        'description' => $item['description'] ?? '',
                    ],
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
            ],
        ]);
        
        return response()->json(['url' => $session->url]);
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
            // Clear the cart after successful payment
            $request->session()->flash('payment_success', true);
            $request->session()->flash('session_id', $sessionId);
        }

        return redirect()->route('cart.index');
    }
}
