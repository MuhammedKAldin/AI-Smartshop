<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class GeminiExportService
{
    /**
     * Export Gemini API call data to separate JSON files.
     * 
     * @param array $requestData
     * @param string $prompt
     * @param array $responseData
     * @param string $context
     * @return array
     */
    public static function exportCall($requestData, $prompt, $responseData = null, $context = 'recommendation')
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        $requestFilename = "recommendation_specs_{$timestamp}.json";
        $responseFilename = "recommendation_res_{$timestamp}.json";
        
        // Export request data
        $requestData = [
            'timestamp' => Carbon::now()->toISOString(),
            'context' => $context,
            'request_data' => $requestData,
            'prompt' => $prompt,
            'metadata' => [
                'user_agent' => request()->header('User-Agent'),
                'ip_address' => request()->ip(),
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'file_type' => 'gemini_request'
            ]
        ];
        
        $requestFilePath = "gemini_calls/{$requestFilename}";
        Storage::put($requestFilePath, json_encode($requestData, JSON_PRETTY_PRINT));
        
        // Export response data (if provided)
        if ($responseData !== null) {
            $responseExportData = [
                'timestamp' => Carbon::now()->toISOString(),
                'context' => $context,
                'response_data' => $responseData,
                'metadata' => [
                    'user_agent' => request()->header('User-Agent'),
                    'ip_address' => request()->ip(),
                    'user_id' => auth()->id(),
                    'session_id' => session()->getId(),
                    'file_type' => 'gemini_response'
                ]
            ];
            
            $responseFilePath = "gemini_response/{$responseFilename}";
            Storage::put($responseFilePath, json_encode($responseExportData, JSON_PRETTY_PRINT));
        }
        
        return [
            'request_file' => $requestFilePath,
            'response_file' => $responseData !== null ? "gemini_response/{$responseFilename}" : null
        ];
    }
    
    /**
     * Get all exported Gemini call files.
     * 
     * @return array
     */
    public static function getExportedFiles()
    {
        $files = Storage::files('gemini_calls');
        return collect($files)->map(function ($file) {
            return [
                'filename' => basename($file),
                'path' => $file,
                'size' => Storage::size($file),
                'last_modified' => Storage::lastModified($file),
            ];
        })->sortByDesc('last_modified')->values()->toArray();
    }
    
    /**
     * Get the content of a specific exported file.
     * 
     * @param string $filename
     * @return array|null
     */
    public static function getFileContent($filename)
    {
        $filePath = "gemini_calls/{$filename}";
        if (Storage::exists($filePath)) {
            return json_decode(Storage::get($filePath), true);
        }
        return null;
    }
    
    /**
     * Delete an exported file.
     * 
     * @param string $filename
     * @return bool
     */
    public static function deleteFile($filename)
    {
        $filePath = "gemini_calls/{$filename}";
        if (Storage::exists($filePath)) {
            return Storage::delete($filePath);
        }
        return false;
    }
    
    /**
     * Clean up old export files (older than specified days).
     * 
     * @param int $days
     * @return int
     */
    public static function cleanupOldFiles($days = 30)
    {
        $files = Storage::files('gemini_calls');
        $cutoffTime = now()->subDays($days)->timestamp;
        $deletedCount = 0;
        
        foreach ($files as $file) {
            if (Storage::lastModified($file) < $cutoffTime) {
                Storage::delete($file);
                $deletedCount++;
            }
        }
        
        return $deletedCount;
    }
}
