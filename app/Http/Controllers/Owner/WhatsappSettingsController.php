<?php

namespace App\Http\Controllers\Owner;

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
        $ownerId = auth()->id();
        $session = WaSession::where('owner_id', $ownerId)->first();

        return Inertia::render('Owner/WhatsappSettings', [
            'session' => $session ? [
                'status' => $session->status,
                'phone_number' => $session->phone_number,
                'connected_at' => $session->connected_at?->toISOString(),
                'disconnected_at' => $session->disconnected_at?->toISOString(),
            ] : null,
        ]);
    }

    /**
     * Start a WhatsApp session using QR code.
     */
    public function startSession()
    {
        $ownerId = auth()->id();
        $result = $this->waService->startSession($ownerId);

        return response()->json($result);
    }

    /**
     * Start a WhatsApp session using pairing code.
     */
    public function startPairingCode(Request $request)
    {
        $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^(\+62|62|08)\d{8,13}$/'],
        ]);

        $ownerId = auth()->id();
        $result = $this->waService->startSessionWithPairingCode($ownerId, $request->phone_number);

        return response()->json($result);
    }

    /**
     * Stop/disconnect the WhatsApp session.
     */
    public function stopSession()
    {
        $ownerId = auth()->id();
        $result = $this->waService->stopSession($ownerId);

        return response()->json($result);
    }

    /**
     * Get current session status (for polling from frontend).
     */
    public function getStatus()
    {
        $ownerId = auth()->id();

        // First try to get live status from WA service
        $liveStatus = $this->waService->getStatus($ownerId);

        // Also get DB session for extra info
        $session = WaSession::where('owner_id', $ownerId)->first();

        return response()->json([
            'success' => true,
            'status' => $liveStatus['status'] ?? ($session?->status ?? 'disconnected'),
            'phone_number' => $liveStatus['phoneNumber'] ?? $session?->phone_number,
            'pairingCode' => $liveStatus['pairingCode'] ?? null,
            'connected_at' => $session?->connected_at?->toISOString(),
        ]);
    }

    /**
     * Get QR code (for polling from frontend).
     */
    public function getQrCode()
    {
        $ownerId = auth()->id();
        $result = $this->waService->getQrCode($ownerId);

        return response()->json($result);
    }
}
