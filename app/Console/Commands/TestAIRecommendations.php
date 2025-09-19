<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\GeminiController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Storage;

class TestAIRecommendations extends Command
{
    protected $signature = 'test:ai-recommendations {user_id=1}';

    protected $description = 'Test AI recommendations with a logged-in user';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $this->info("Testing AI recommendations for user ID: {$userId}");

        // Create a mock request
        $request = new Request;
        $request->merge([
            'user_id' => $userId,
            'product_id' => 1,
            'cart_items' => [
                ['id' => 2, 'name' => 'Test Product', 'quantity' => 1, 'price' => 29.99],
            ],
            'context' => 'product_view',
        ]);

        // Create controller instance
        $controller = new GeminiController;

        // Make the request
        $response = $controller->recommendations($request);

        $this->info('Response received:');
        $this->line(json_encode($response->getData(), JSON_PRETTY_PRINT));

        // Check if files were created
        $this->line('');
        $this->info('Checking exported files...');

        $requestFiles = Storage::files('gemini_calls');
        $responseFiles = Storage::files('gemini_response');

        $this->info('Request files: '.count($requestFiles));
        $this->info('Response files: '.count($responseFiles));

        if (! empty($requestFiles)) {
            $latestRequest = collect($requestFiles)->sort()->last();
            $this->info('Latest request file: '.basename($latestRequest));
        }

        if (! empty($responseFiles)) {
            $latestResponse = collect($responseFiles)->sort()->last();
            $this->info('Latest response file: '.basename($latestResponse));
        }

        return Command::SUCCESS;
    }
}
