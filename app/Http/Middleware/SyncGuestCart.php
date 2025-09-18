<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\CartService;
use Symfony\Component\HttpFoundation\Response;

class SyncGuestCart
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user just logged in and has guest cart items
        if (Auth::check() && session()->has('cart') && !empty(session('cart'))) {
            $this->cartService->syncGuestCartToUser(Auth::id());
        }

        return $next($request);
    }
}