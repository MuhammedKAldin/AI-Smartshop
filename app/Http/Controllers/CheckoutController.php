<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CartService;

class CheckoutController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Display the checkout page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Validate cart for checkout
        $validation = $this->cartService->validateCartForCheckout();

        if (! $validation['valid']) {
            // Remove out of stock items
            $this->cartService->removeOutOfStockItems($validation['out_of_stock']);

            return redirect()->route('cart.index')->with([
                'error' => 'Some items in your cart are no longer available',
                'out_of_stock_items' => $validation['out_of_stock'],
                'insufficient_stock_items' => $validation['insufficient_stock'],
            ]);
        }

        $cartItems = $this->cartService->getCartItems();
        $cartTotal = $this->cartService->getCartTotal();
        $cartItemCount = $this->cartService->getCartItemCount();

        return view('checkout.index', compact('cartItems', 'cartTotal', 'cartItemCount'));
    }
}
