<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StockReservationService;
use App\Models\Product;
use App\Models\StockReservation;

class TestConcurrencyScenario extends Command
{
    protected $signature = 'test:concurrency {product_id=1}';
    protected $description = 'Test 6 users trying to buy 1 item';

    protected $stockReservationService;

    public function __construct(StockReservationService $stockReservationService)
    {
        parent::__construct();
        $this->stockReservationService = $stockReservationService;
    }

    public function handle()
    {
        $productId = $this->argument('product_id');
        $product = Product::find($productId);
        
        if (!$product) {
            $this->error("Product {$productId} not found");
            return;
        }

        $this->info("Testing concurrency for product: {$product->name}");
        $this->info("Initial stock: {$product->stock}");
        $this->info("Initial available: {$this->stockReservationService->getAvailableStock($productId)}");
        $this->line('');

        // Simulate 6 users trying to reserve 1 item each
        $results = [];
        
        for ($i = 1; $i <= 6; $i++) {
            $this->info("User {$i} attempting reservation...");
            
            try {
                $reservation = $this->stockReservationService->reserveStock(
                    $productId,
                    1,
                    "test_order_{$i}",
                    $i,
                    "session_{$i}"
                );
                
                $results[] = [
                    'user' => $i,
                    'status' => 'SUCCESS',
                    'reservation_id' => $reservation->id,
                    'available_after' => $this->stockReservationService->getAvailableStock($productId)
                ];
                
                $this->info("✅ User {$i}: SUCCESS - Reservation ID: {$reservation->id}");
                
            } catch (\Exception $e) {
                $results[] = [
                    'user' => $i,
                    'status' => 'FAILED',
                    'error' => $e->getMessage(),
                    'available_after' => $this->stockReservationService->getAvailableStock($productId)
                ];
                
                $this->error("❌ User {$i}: FAILED - {$e->getMessage()}");
            }
        }

        $this->line('');
        $this->info('=== RESULTS SUMMARY ===');
        
        $successCount = collect($results)->where('status', 'SUCCESS')->count();
        $failedCount = collect($results)->where('status', 'FAILED')->count();
        
        $this->info("✅ Successful reservations: {$successCount}");
        $this->info("❌ Failed reservations: {$failedCount}");
        $this->info("📦 Final available stock: {$this->stockReservationService->getAvailableStock($productId)}");
        
        // Show active reservations
        $activeReservations = StockReservation::where('product_id', $productId)
            ->where('status', 'active')
            ->get();
            
        $this->line('');
        $this->info('=== ACTIVE RESERVATIONS ===');
        foreach ($activeReservations as $reservation) {
            $this->info("Reservation ID: {$reservation->id} | User: {$reservation->user_id} | Quantity: {$reservation->quantity} | Expires: {$reservation->reserved_until}");
        }
    }
}
