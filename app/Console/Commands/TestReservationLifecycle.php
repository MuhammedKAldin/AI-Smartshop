<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StockReservationService;
use App\Models\Product;
use App\Models\StockReservation;
use App\Models\User;

class TestReservationLifecycle extends Command
{
    protected $signature = 'test:reservation-lifecycle';
    protected $description = 'Test the complete reservation lifecycle';

    protected $stockReservationService;

    public function __construct(StockReservationService $stockReservationService)
    {
        parent::__construct();
        $this->stockReservationService = $stockReservationService;
    }

    public function handle()
    {
        $this->info('Testing Stock Reservation Lifecycle...');
        
        // Get a product with stock
        $product = Product::where('stock', '>', 0)->first();
        if (!$product) {
            $this->error('No products with stock found');
            return Command::FAILURE;
        }
        
        $this->info("Testing with product: {$product->name} (Stock: {$product->stock})");
        
        // Test 1: Initial state
        $this->line('');
        $this->info('=== TEST 1: Initial State ===');
        $availableStock = $this->stockReservationService->getAvailableStock($product->id);
        $this->info("Available stock: {$availableStock}");
        
        // Test 2: User A reserves stock
        $this->line('');
        $this->info('=== TEST 2: User A Reserves Stock ===');
        $orderTokenA = 'test_order_a_' . time();
        
        try {
            $reservationA = $this->stockReservationService->reserveStock(
                $product->id,
                1,
                $orderTokenA,
                1,
                'session_a'
            );
            
            $this->info("✅ User A reservation created: ID {$reservationA->id}");
            
            $availableStock = $this->stockReservationService->getAvailableStock($product->id);
            $this->info("Available stock after User A reservation: {$availableStock}");
            
        } catch (\Exception $e) {
            $this->error("❌ User A reservation failed: " . $e->getMessage());
        }
        
        // Test 3: User B tries to reserve (should fail)
        $this->line('');
        $this->info('=== TEST 3: User B Tries to Reserve (Should Fail) ===');
        $orderTokenB = 'test_order_b_' . time();
        
        try {
            $reservationB = $this->stockReservationService->reserveStock(
                $product->id,
                1,
                $orderTokenB,
                2,
                'session_b'
            );
            
            $this->error("❌ User B reservation should have failed but succeeded!");
            
        } catch (\Exception $e) {
            $this->info("✅ User B reservation correctly failed: " . $e->getMessage());
        }
        
        // Test 4: User A confirms reservation (completes purchase)
        $this->line('');
        $this->info('=== TEST 4: User A Confirms Reservation (Completes Purchase) ===');
        
        $this->stockReservationService->confirmReservation($orderTokenA);
        $this->info("✅ User A reservation confirmed");
        
        $availableStock = $this->stockReservationService->getAvailableStock($product->id);
        $this->info("Available stock after User A confirmation: {$availableStock}");
        
        // Test 5: User B tries again (should succeed now)
        $this->line('');
        $this->info('=== TEST 5: User B Tries Again (Should Succeed Now) ===');
        
        try {
            $reservationB = $this->stockReservationService->reserveStock(
                $product->id,
                1,
                $orderTokenB,
                2,
                'session_b'
            );
            
            $this->info("✅ User B reservation now succeeds: ID {$reservationB->id}");
            
            $availableStock = $this->stockReservationService->getAvailableStock($product->id);
            $this->info("Available stock after User B reservation: {$availableStock}");
            
        } catch (\Exception $e) {
            $this->error("❌ User B reservation still failed: " . $e->getMessage());
        }
        
        // Test 6: Check reservation statuses
        $this->line('');
        $this->info('=== TEST 6: Reservation Statuses ===');
        
        $reservations = StockReservation::where('product_id', $product->id)->get();
        foreach ($reservations as $reservation) {
            $this->info("Reservation {$reservation->id}: Status = {$reservation->status}, Order Token = {$reservation->order_token}");
        }
        
        // Cleanup
        $this->line('');
        $this->info('=== CLEANUP ===');
        try {
            $this->stockReservationService->cancelReservationByToken($orderTokenB);
            $this->info('Test reservations cleaned up');
        } catch (\Exception $e) {
            $this->warn('Cleanup warning: ' . $e->getMessage());
        }
        
        return Command::SUCCESS;
    }
}
