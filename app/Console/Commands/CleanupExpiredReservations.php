<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StockReservationService;

class CleanupExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired stock reservations';

    protected $stockReservationService;

    public function __construct(StockReservationService $stockReservationService)
    {
        parent::__construct();
        $this->stockReservationService = $stockReservationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of expired reservations...');
        
        $cleanedCount = $this->stockReservationService->cleanupExpiredReservations();
        
        $this->info("Cleaned up {$cleanedCount} expired reservations.");
        
        return Command::SUCCESS;
    }
}