<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Display the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // Eager load relationships and select only needed columns
        $product = Product::select(['id', 'name', 'description', 'price', 'stock', 'image', 'category', 'in_stock', 'rating', 'reviews_count', 'tags'])
            ->with(['userInteractions' => function ($query) {
                $query->select(['id', 'product_id', 'interaction_type', 'created_at']);
            }])
            ->findOrFail($id);

        return view('products.show', compact('product'));
    }
}
