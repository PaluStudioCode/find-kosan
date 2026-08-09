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
        $http = Http::baseUrl($this->baseUrl.'/api')
            ->timeout(30)
            ->acceptJson();

        if ($this->apiKey) {
            $http = $http->withHeaders(['X-API-Key' => $this->apiKey]);
        }

        return $http;
    }

    /**
     * Normalize the response from wa-service into a consistent shape.
     *
     * wa-service returns `{ success: true, ...payload }` on success,
     * and `{ success: false, error: "..." }` (with non-2xx HTTP status)
     * on failure. This method enforces that contract so callers can
     * reliably check `success` and `status`.
     *
     * @return array{success: bool, status: ?string, error: ?string}
     */
    protected function normalizeResponse($response, array $context = []): array
    {
        $data = $response->json();

        // Non-2xx response = failure, regardless of body.
        if ($response->failed()) {
            $error = $data['error'] ?? 'WA service error (HTTP '.$response->status().')';

            Log::error('WA Service error response', array_merge($context, [
                'http_status' => $response->status(),
                'error' => $error,
            ]));

            return array_merge([
                'success' => false,
                'status' => null,
                'error' => $error,
            ], $data ?? []);
        }

        // 2xx but missing/invalid body.
        if (! is_array($data)) {
            Log::error('WA Service empty/invalid response body', $context);

            return [
                'success' => false,
                'status' => null,
                'error' => 'Empty response from WA service',
            ];
        }

        // Preserve everything wa-service returned, but ensure
        // `success` and `status` keys always exist for callers.
        return array_merge([
            'success' => false,
            'status' => null,
            'error' => null,
        ], $data);
    }

    /**
     * Start a WhatsApp session for an owner (QR Code mode).
     */
    public function startSession(int $adminId): array
    {
        try {
            $response = $this->request()->post("/sessions/{$adminId}/start", [
                'usePairingCode' => false,
            ]);

            return $this->normalizeResponse($response, ['method' => 'startSession', 'admin_id' => $adminId]);
        } catch (\Exception $e) {
            Log::error('WA Service startSession error: '.$e->getMessage());

            return ['success' => false, 'status' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Start a WhatsApp session for an owner (Pairing Code mode).
     */
    public function startSessionWithPairingCode(int $adminId, string $phoneNumber): array
    {
        try {
            $response = $this->request()->post("/sessions/{$adminId}/start", [
                'usePairingCode' => true,
                'phoneNumber' => $phoneNumber,
            ]);

            return $this->normalizeResponse($response, ['method' => 'startSessionWithPairingCode', 'admin_id' => $adminId]);
        } catch (\Exception $e) {
            Log::error('WA Service startSessionWithPairingCode error: '.$e->getMessage());

            return ['success' => false, 'status' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Stop/disconnect an owner's WhatsApp session.
     */
    public function stopSession(int $adminId): array
    {
        try {
            $response = $this->request()->post("/sessions/{$adminId}/stop");

            return $this->normalizeResponse($response, ['method' => 'stopSession', 'admin_id' => $adminId]);
        } catch (\Exception $e) {
            Log::error('WA Service stopSession error: '.$e->getMessage());

            return ['success' => false, 'status' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get session status for an owner.
     *
     * @return array{success: bool, status: ?string, phone_number: ?string, connected_at: ?string}
     */
    public function getStatus(int $adminId): array
    {
        try {
            $response = $this->request()->get("/sessions/{$adminId}/status");

            $data = $this->normalizeResponse($response, ['method' => 'getStatus', 'admin_id' => $adminId]);

            return [
                'success' => $data['success'],
                'status' => $data['status'] ?? 'disconnected',
                'phoneNumber' => $data['phoneNumber'] ?? null,
                'pairingCode' => $data['pairingCode'] ?? null,
                'qr' => $data['qr'] ?? null,
                'error' => $data['error'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('WA Service getStatus error: '.$e->getMessage());

            return [
                'success' => false,
                'status' => null,
                'phoneNumber' => null,
                'pairingCode' => null,
                'qr' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get QR code for an owner's session.
     *
     * @return array{success: bool, status: ?string, qr: ?string, pairingCode: ?string, phoneNumber: ?string}
     */
    public function getQrCode(int $adminId): array
    {
        try {
            $response = $this->request()->get("/sessions/{$adminId}/qr");

            $data = $this->normalizeResponse($response, ['method' => 'getQrCode', 'admin_id' => $adminId]);

            return [
                'success' => $data['success'],
                'status' => $data['status'] ?? null,
                'qr' => $data['qr'] ?? null,
                'pairingCode' => $data['pairingCode'] ?? null,
                'phoneNumber' => $data['phoneNumber'] ?? null,
                'error' => $data['error'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('WA Service getQrCode error: '.$e->getMessage());

            return [
                'success' => false,
                'status' => null,
                'qr' => null,
                'pairingCode' => null,
                'phoneNumber' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a WhatsApp message using an owner's session.
     *
     * @param  int  $adminId  Session admin_id (0 = SuperAdmin/system session)
     * @param  string  $phoneOrJid  Nomor telepon (mis. "628xxx") ATAU JID lengkap (mis. "628xxx@s.whatsapp.net" / "173xxx@lid")
     * @param  string  $message  Isi pesan
     * @param  bool  $useRawJid  Jika true, $phoneOrJid dianggap JID lengkap & dikirim apa adanya ke wa-service
     *                           (penting untuk @lid yang tidak bisa di-normalize ke nomor telepon)
     */
    public function sendMessage(int $adminId, string $phoneOrJid, string $message, bool $useRawJid = false): array
    {
        try {
            $response = $this->request()->post("/sessions/{$adminId}/send", [
                'phone' => $phoneOrJid,
                'message' => $message,
                'use_raw_jid' => $useRawJid,
            ]);

            $data = $response->json() ?? [];

            if (! empty($data['success'])) {
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
            Log::error('WA Service sendMessage error: '.$e->getMessage());

            return [
                'status' => false,
                'reason' => 'WA Service tidak dapat dihubungi: '.$e->getMessage(),
            ];
        }
    }
}
