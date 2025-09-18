<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class HomeController extends Controller
{
    /**
     * Display the home page with products.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Eager load relationships to prevent N+1 queries
        $products = Product::select(['id', 'name', 'description', 'price', 'image', 'category', 'in_stock', 'rating', 'reviews_count', 'tags'])
            ->inStock()
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('home', compact('products'));
    }
}
