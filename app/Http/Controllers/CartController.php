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

        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'stock_validation'
            ], 400);
        }
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

        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'stock_issue'
            ], 400);
        }
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
    
    /**
     * Validate cart stock and return out of stock items.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateStock()
    {
        $outOfStockItems = $this->cartService->validateCartStock();
        
        return response()->json([
            'out_of_stock_items' => $outOfStockItems,
            'has_out_of_stock' => !empty($outOfStockItems)
        ]);
    }
    
    /**
     * Remove out of stock items from cart.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeOutOfStock()
    {
        $outOfStockItems = $this->cartService->validateCartStock();
        $removedItems = $this->cartService->removeOutOfStockItems($outOfStockItems);
        
        return response()->json([
            'success' => true,
            'removed_items' => $removedItems,
            'cart_items' => $this->cartService->getCartItems(),
            'cart_total' => $this->cartService->getCartTotal(),
            'cart_item_count' => $this->cartService->getCartItemCount()
        ]);
    }
}
