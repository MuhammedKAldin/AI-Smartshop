<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestGeminiAPI extends Command
{
    protected $signature = 'test:gemini-api';
    protected $description = 'Test Gemini API directly';

    public function handle()
    {
        $this->info('Testing Gemini API directly...');
        
        $apiKey = config('ai.gemini.api_key');
        $endpoint = config('ai.model.endpoint_for_model')(null);
        
        $this->info("API Key: " . substr($apiKey, 0, 10) . "...");
        $this->info("Endpoint: " . $endpoint);
        
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => 'Hello, can you respond with just the number 42?']],
                ],
            ],
        ];
        
        $this->info('Making API call...');
        
        try {
            $response = Http::asJson()->post($endpoint . '?key=' . $apiKey, $payload);
            
            $this->info('Response Status: ' . $response->status());
            $this->info('Response Headers: ' . json_encode($response->headers()));
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info('Response Data: ' . json_encode($data, JSON_PRETTY_PRINT));
            } else {
                $this->error('Error Response: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
        }
        
        return Command::SUCCESS;
    }
}
