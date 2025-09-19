<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'image',
        'category',
        'in_stock',
        'rating',
        'reviews_count',
        'tags',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'in_stock' => 'boolean',
        'rating' => 'decimal:1',
        'reviews_count' => 'integer',
        'tags' => 'array',
    ];

    // Relationships
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function userInteractions(): HasMany
    {
        return $this->hasMany(UserInteraction::class);
    }

    // Scopes
    public function scopeInStock($query)
    {
        return $query->where('in_stock', true)->where('stock', '>', 0);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessors & Mutators
    public function getInStockAttribute($value)
    {
        return $this->stock > 0 && $value;
    }

    public function setInStockAttribute($value)
    {
        $this->attributes['in_stock'] = $value && $this->stock > 0;
    }

    public function getImageAttribute($value)
    {
        if ($value && ! str_starts_with($value, 'http')) {
            return '/storage/'.$value;
        }

        return $value;
    }
}
