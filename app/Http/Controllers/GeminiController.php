<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiController extends Controller
{
    // Simple GET endpoint: /ai?prompt=...
    public function ai(Request $request)
    {
        try {
            $userPrompt = $request->query('prompt');
            $debug = $request->query('debug', false);

            if (!$userPrompt) {
                return response()->json([
                    'error' => 'Prompt is required'
                ], 400);
            }

            $apiKey = config('ai.gemini.api_key');
            if (!$apiKey) {
                return response()->json([
                    'error' => 'Missing GEMINI_API_KEY in environment'
                ], 500);
            }

            $endpoint = config('ai.model.endpoint_for_model')(null);

            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [ ['text' => $userPrompt] ],
                    ],
                ],
            ];

            $response = Http::asJson()->post($endpoint.'?key='.$apiKey, $payload);

            $responseData = $response->json();
            
            if (!$debug) {
                // Use the centralized response parsing from ai.php configuration
                return config('ai.model.extract_response_content')($responseData);
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to get response AI',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
