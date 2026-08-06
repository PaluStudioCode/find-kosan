<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\WhatsappNumber;
use App\Http\Controllers\Controller;
use App\Models\WaSession;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsappSettingsController extends Controller
{
    protected WhatsappService $waService;

    public function __construct(WhatsappService $waService)
    {
        $this->waService = $waService;
    }

    /**
     * Display the WhatsApp settings page.
     */
    public function index()
    {
        $adminId = auth()->id();
        return Inertia::render('Admin/WhatsappSettings', [
            'session' => Inertia::defer(function () use ($adminId) {
                $session = WaSession::where('admin_id', $adminId)->first();
                return $session ? [
                    'status' => $session->status,
                    'phone_number' => $session->phone_number,
                    'connected_at' => $session->connected_at?->toISOString(),
                    'disconnected_at' => $session->disconnected_at?->toISOString(),
                ] : null;
            }),
        ]);
    }

    /**
     * Start a WhatsApp session using QR code.
     */
    public function startSession()
    {
        $adminId = auth()->id();
        $result = $this->waService->startSession($adminId);

        return response()->json($result);
    }

    /**
     * Start a WhatsApp session using pairing code.
     */
    public function startPairingCode(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => [
                'required',
                'string',
                'regex:/^(\+62|62|08)\d{8,13}$/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! WhatsappNumber::isValid($value)) {
                        $fail('Nomor WhatsApp tidak valid. Pastikan nomor aktif (9-13 digit setelah prefix 62/08).');
                    }
                },
            ],
        ]);

        $adminId = auth()->id();
        // Normalize to 62xxx before sending to wa-service for consistency.
        $normalized = WhatsappNumber::normalize($validated['phone_number']);
        $result = $this->waService->startSessionWithPairingCode($adminId, $normalized);

        return response()->json($result);
    }

    /**
     * Stop/disconnect the WhatsApp session.
     */
    public function stopSession()
    {
        $adminId = auth()->id();
        $result = $this->waService->stopSession($adminId);

        return response()->json($result);
    }

    /**
     * Get current session status (for polling from frontend).
     *
     * wa-service is the source of truth for live status. The `wa_sessions`
     * table is kept in sync by wa-service itself (it writes on connect/
     * disconnect). We still merge DB info as a fallback in case the
     * wa-service is unreachable but the DB still has a recent state.
     */
    public function getStatus()
    {
        $adminId = auth()->id();

        // First try to get live status from WA service
        $liveStatus = $this->waService->getStatus($adminId);

        // Also get DB session for extra info (connected_at, etc.)
        $session = WaSession::where('admin_id', $adminId)->first();

        $status = $liveStatus['status'] ?? ($session?->status ?? 'disconnected');
        $phoneNumber = $liveStatus['phoneNumber'] ?? $session?->phone_number;
        $connectedAt = $session?->connected_at?->toISOString();

        // If live status is disconnected but DB still says connected,
        // the session was likely dropped on the wa-service side. Mark
        // it disconnected in DB so subsequent page loads are consistent.
        if ($status === 'disconnected' && $session && $session->status !== 'disconnected') {
            $session->update([
                'status' => 'disconnected',
                'disconnected_at' => now(),
            ]);
            $connectedAt = null;
        }

        return response()->json([
            'success' => $liveStatus['success'] ?? true,
            'status' => $status,
            'phone_number' => $phoneNumber,
            'pairingCode' => $liveStatus['pairingCode'] ?? null,
            'connected_at' => $connectedAt,
        ]);
    }

    /**
     * Get QR code (for polling from frontend).
     */
    public function getQrCode()
    {
        $adminId = auth()->id();
        $result = $this->waService->getQrCode($adminId);

        return response()->json($result);
    }
}
