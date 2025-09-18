<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeminiExportService;

class TestGeminiExport extends Command
{
    protected $signature = 'test:gemini-export';
    protected $description = 'Test Gemini export functionality';

    public function handle()
    {
        $this->info('Testing Gemini Export System...');
        
        // Test data
        $requestData = [
            'user_id' => 1,
            'product_id' => 5,
            'cart_items' => [
                ['id' => 1, 'name' => 'Test Product', 'quantity' => 2, 'price' => 29.99]
            ],
            'context' => 'product_view',
            'total_products_available' => 50
        ];
        
        $prompt = "Based on user profile and current product, recommend 4 personalized products...";
        
        $responseData = [
            'raw_response' => [
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '[1, 5, 12, 23]']
                            ]
                        ]
                    ]
                ]
            ],
            'extracted_response' => '[1, 5, 12, 23]',
            'parsed_product_ids' => [1, 5, 12, 23],
            'success' => true
        ];
        
        // Test export
        $result = GeminiExportService::exportCall(
            $requestData,
            $prompt,
            $responseData,
            'product_view'
        );
        
        $this->info('Export completed!');
        $this->info('Request file: ' . $result['request_file']);
        $this->info('Response file: ' . $result['response_file']);
        
        // Check if files exist
        if (\Storage::exists($result['request_file'])) {
            $this->info('✅ Request file created successfully');
        } else {
            $this->error('❌ Request file not found');
        }
        
        if (\Storage::exists($result['response_file'])) {
            $this->info('✅ Response file created successfully');
        } else {
            $this->error('❌ Response file not found');
        }
        
        return Command::SUCCESS;
    }
}
