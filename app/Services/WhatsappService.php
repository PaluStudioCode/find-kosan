<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.wa_service.url', 'http://localhost:3001'), '/');
        $this->apiKey = config('services.wa_service.api_key');
    }

    /**
     * Make an HTTP request to the WA service.
     */
    protected function request()
    {
        $http = Http::baseUrl($this->baseUrl . '/api')
            ->timeout(30)
            ->acceptJson();

        if ($this->apiKey) {
            $http = $http->withHeaders(['X-API-Key' => $this->apiKey]);
        }

        return $http;
    }

    /**
     * Start a WhatsApp session for an owner (QR Code mode).
     */
    public function startSession(int $ownerId): array
    {
        try {
            $response = $this->request()->post("/sessions/{$ownerId}/start", [
                'usePairingCode' => false,
            ]);

            return $response->json() ?? ['success' => false, 'error' => 'Empty response'];
        } catch (\Exception $e) {
            Log::error("WA Service startSession error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Start a WhatsApp session for an owner (Pairing Code mode).
     */
    public function startSessionWithPairingCode(int $ownerId, string $phoneNumber): array
    {
        try {
            $response = $this->request()->post("/sessions/{$ownerId}/start", [
                'usePairingCode' => true,
                'phoneNumber' => $phoneNumber,
            ]);

            return $response->json() ?? ['success' => false, 'error' => 'Empty response'];
        } catch (\Exception $e) {
            Log::error("WA Service startSessionWithPairingCode error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Stop/disconnect an owner's WhatsApp session.
     */
    public function stopSession(int $ownerId): array
    {
        try {
            $response = $this->request()->post("/sessions/{$ownerId}/stop");
            return $response->json() ?? ['success' => false, 'error' => 'Empty response'];
        } catch (\Exception $e) {
            Log::error("WA Service stopSession error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get session status for an owner.
     */
    public function getStatus(int $ownerId): array
    {
        try {
            $response = $this->request()->get("/sessions/{$ownerId}/status");
            return $response->json() ?? ['success' => false, 'error' => 'Empty response'];
        } catch (\Exception $e) {
            Log::error("WA Service getStatus error: " . $e->getMessage());
            return ['success' => false, 'status' => 'disconnected', 'error' => $e->getMessage()];
        }
    }

    /**
     * Get QR code for an owner's session.
     */
    public function getQrCode(int $ownerId): array
    {
        try {
            $response = $this->request()->get("/sessions/{$ownerId}/qr");
            return $response->json() ?? ['success' => false, 'error' => 'Empty response'];
        } catch (\Exception $e) {
            Log::error("WA Service getQrCode error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a WhatsApp message using an owner's session.
     */
    public function sendMessage(int $ownerId, string $phoneNumber, string $message): array
    {
        try {
            $response = $this->request()->post("/sessions/{$ownerId}/send", [
                'phone' => $phoneNumber,
                'message' => $message,
            ]);

            $data = $response->json() ?? [];

            if (!empty($data['success'])) {
                return [
                    'status' => true,
                    'response' => $data,
                ];
            }

            return [
                'status' => false,
                'reason' => $data['reason'] ?? $data['error'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            Log::error("WA Service sendMessage error: " . $e->getMessage());
            return [
                'status' => false,
                'reason' => 'WA Service tidak dapat dihubungi: ' . $e->getMessage(),
            ];
        }
    }
}
