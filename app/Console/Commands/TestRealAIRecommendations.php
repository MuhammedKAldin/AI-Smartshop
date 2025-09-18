<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\GeminiController;

class TestRealAIRecommendations extends Command
{
    protected $signature = 'test:real-ai {user_id=1}';
    protected $description = 'Test real AI recommendations with proper authentication';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $this->info("Testing REAL AI recommendations for user ID: {$userId}");
        
        // Get the user and authenticate them
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return Command::FAILURE;
        }
        
        // Authenticate the user
        Auth::login($user);
        $this->info("Authenticated as: {$user->name} ({$user->email})");
        
        // Create a proper request
        $request = new Request();
        $request->merge([
            'product_id' => 1,
            'cart_items' => [
                ['id' => 2, 'name' => 'Test Product', 'quantity' => 1, 'price' => 29.99]
            ],
            'context' => 'product_view'
        ]);
        
        // Create controller instance
        $controller = new GeminiController();
        
        // Make the request
        $response = $controller->recommendations($request);
        $responseData = $response->getData();
        
        $this->info('Response received:');
        $this->line("Source: " . $responseData->source);
        $this->line("Personalized: " . ($responseData->personalized ? 'Yes' : 'No'));
        
        if (isset($responseData->algorithm)) {
            $this->line("Algorithm: " . $responseData->algorithm);
        }
        
        $this->line("Recommendations count: " . count($responseData->recommendations));
        
        // Check if files were created
        $this->line('');
        $this->info('Checking exported files...');
        
        $requestFiles = \Storage::files('gemini_calls');
        $responseFiles = \Storage::files('gemini_response');
        
        $this->info('Total request files: ' . count($requestFiles));
        $this->info('Total response files: ' . count($responseFiles));
        
        if (!empty($requestFiles)) {
            $latestRequest = collect($requestFiles)->sort()->last();
            $this->info('Latest request file: ' . basename($latestRequest));
            
            // Check the content
            $content = json_decode(\Storage::get($latestRequest), true);
            if (isset($content['metadata']['user_id'])) {
                $this->info('User ID in file: ' . $content['metadata']['user_id']);
            }
        }
        
        if (!empty($responseFiles)) {
            $latestResponse = collect($responseFiles)->sort()->last();
            $this->info('Latest response file: ' . basename($latestResponse));
        }
        
        return Command::SUCCESS;
    }
}
