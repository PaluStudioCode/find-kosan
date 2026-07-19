<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public function sendMessage(string $phoneNumber, string $message)
    {
        $apiKey = config('services.fonnte.api_key');
        if (!$apiKey) {
            Log::info("Fonnte API key not set. Simulating sending WA to $phoneNumber: $message");
            return [
                'status' => true,
                'response' => ['simulated' => true]
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey
            ])->post('https://api.fonnte.com/send', [
                'target' => $phoneNumber,
                'message' => $message,
                'countryCode' => '62', // Optional: forces 62 instead of 0
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'response' => $response->json(),
                ];
            } else {
                return [
                    'status' => false,
                    'reason' => $response->body()
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'reason' => $e->getMessage()
            ];
        }
    }
}
