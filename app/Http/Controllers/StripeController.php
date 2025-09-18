<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StripeController extends Controller
{
    public function index()
    {
        return view('index');
    }

    public function checkout()
    {
        \Stripe\Stripe::setApiKey(config('stripe.sk'));
        $session = \Stripe\Checkout\Session::create([
        'line_items' => [
            [
                'price_data' => [
                    'currency' => 'egp',
                    'product_data' => [
                        'name' => 'Test Product',
                    ],
                    'unit_amount' => 2500,
                ],
                'quantity' => 1,
            ],
        ],
        'mode' => 'payment',
        'success_url' => route('stripe.success'),
        'cancel_url' => route('stripe.index'),
        ]);
        
        return redirect()->away($session->url);
    }

    public function success()
    {
        return view('index');
    }
}
