<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CartService;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Display the cart page.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $cartItems = $this->cartService->getCartItems();
        $cartTotal = $this->cartService->getCartTotal();
        $cartItemCount = $this->cartService->getCartItemCount();

        return view('cart.index', compact('cartItems', 'cartTotal', 'cartItemCount'));
    }

    /**
     * Add item to cart.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1|max:10'
        ]);

        $cartItems = $this->cartService->addToCart(
            $request->product_id,
            $request->quantity ?? 1
        );

        return response()->json([
            'success' => true,
            'cart_items' => $cartItems,
            'cart_total' => $this->cartService->getCartTotal(),
            'cart_item_count' => $this->cartService->getCartItemCount()
        ]);
    }

    /**
     * Update cart item quantity.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0|max:10'
        ]);

        $cartItems = $this->cartService->updateCartItem(
            $request->product_id,
            $request->quantity
        );

        return response()->json([
            'success' => true,
            'cart_items' => $cartItems,
            'cart_total' => $this->cartService->getCartTotal(),
            'cart_item_count' => $this->cartService->getCartItemCount()
        ]);
    }

    /**
     * Remove item from cart.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $cartItems = $this->cartService->removeFromCart($request->product_id);

        return response()->json([
            'success' => true,
            'cart_items' => $cartItems,
            'cart_total' => $this->cartService->getCartTotal(),
            'cart_item_count' => $this->cartService->getCartItemCount()
        ]);
    }

    /**
     * Clear cart.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function clear()
    {
        $this->cartService->clearCart();

        return response()->json([
            'success' => true,
            'cart_items' => [],
            'cart_total' => 0,
            'cart_item_count' => 0
        ]);
    }

    /**
     * Get cart data for API.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCartData()
    {
        return response()->json([
            'cart_items' => $this->cartService->getCartItems(),
            'cart_total' => $this->cartService->getCartTotal(),
            'cart_item_count' => $this->cartService->getCartItemCount()
        ]);
    }
}
