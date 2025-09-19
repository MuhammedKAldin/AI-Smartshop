<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable(); // For guest users
            $table->integer('quantity');
            $table->datetime('reserved_until');
            $table->enum('status', ['active', 'confirmed', 'expired', 'cancelled'])->default('active');
            $table->string('order_token')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['product_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['reserved_until']);
            $table->index(['order_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
