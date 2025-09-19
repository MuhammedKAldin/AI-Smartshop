<?php

declare(strict_types=1);

return [
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
    ],

    'model' => [
        // Helper to get the fully qualified model endpoint path
        'endpoint_for_model' => function (?string $model = null): string {
            $selectedModel = $model ?: config('ai.gemini.model');
            $base = rtrim(config('ai.gemini.endpoint'), '/');

            return $base.'/'.trim($selectedModel, '/').':generateContent';
        },

        // Helper to extract response content text
        'extract_response_content' => function (array $response): Illuminate\Http\JsonResponse {
            $text = null;
            if (isset($response['candidates'][0]['content']['parts'])) {
                $parts = $response['candidates'][0]['content']['parts'];
                foreach ($parts as $part) {
                    if (isset($part['text'])) {
                        $text = $part['text'];
                        break;
                    }
                }
            }

            if ($text === null) {
                return response()->json([
                    'error' => 'No content returned from Gemini',
                    'raw' => $response,
                ], 502);
            }

            return response()->json([
                'content' => $text,
            ]);
        },
    ],
];
