<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StockReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'session_id',
        'quantity',
        'reserved_until',
        'status', // 'active', 'confirmed', 'expired', 'cancelled'
        'order_token'
    ];

    protected $casts = [
        'reserved_until' => 'datetime',
        'quantity' => 'integer'
    ];

    /**
     * Get the product that owns the reservation.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user that owns the reservation.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active reservations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('reserved_until', '>', now());
    }

    /**
     * Scope for expired reservations.
     */
    public function scopeExpired($query)
    {
        return $query->where('reserved_until', '<=', now());
    }

    /**
     * Check if reservation is still valid.
     */
    public function isValid()
    {
        return $this->status === 'active' && $this->reserved_until > now();
    }

    /**
     * Mark reservation as confirmed (order created).
     */
    public function markAsConfirmed()
    {
        $this->update(['status' => 'confirmed']);
    }

    /**
     * Mark reservation as expired.
     */
    public function markAsExpired()
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Mark reservation as cancelled.
     */
    public function markAsCancelled()
    {
        $this->update(['status' => 'cancelled']);
    }
}
