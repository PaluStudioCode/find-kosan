<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaSession;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsappSettingsController extends Controller
{
    protected WhatsappService $waService;
    const ADMIN_SESSION_ID = 0;

    public function __construct(WhatsappService $waService)
    {
        $this->waService = $waService;
    }

    public function index()
    {
        $session = WaSession::where('owner_id', self::ADMIN_SESSION_ID)->first();

        return Inertia::render('Admin/WhatsappSettings', [
            'session' => $session ? [
                'status' => $session->status,
                'phone_number' => $session->phone_number,
                'connected_at' => $session->connected_at?->toISOString(),
                'disconnected_at' => $session->disconnected_at?->toISOString(),
            ] : null,
        ]);
    }

    public function startSession()
    {
        $result = $this->waService->startSession(self::ADMIN_SESSION_ID);
        return response()->json($result);
    }

    public function startPairingCode(Request $request)
    {
        $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^(\+62|62|08)\d{8,13}$/'],
        ]);
        $result = $this->waService->startSessionWithPairingCode(self::ADMIN_SESSION_ID, $request->phone_number);
        return response()->json($result);
    }

    public function stopSession()
    {
        $result = $this->waService->stopSession(self::ADMIN_SESSION_ID);
        return response()->json($result);
    }

    public function getStatus()
    {
        $liveStatus = $this->waService->getStatus(self::ADMIN_SESSION_ID);
        $session = WaSession::where('owner_id', self::ADMIN_SESSION_ID)->first();

        return response()->json([
            'success' => true,
            'status' => $liveStatus['status'] ?? ($session?->status ?? 'disconnected'),
            'phone_number' => $liveStatus['phoneNumber'] ?? $session?->phone_number,
            'pairingCode' => $liveStatus['pairingCode'] ?? null,
            'connected_at' => $session?->connected_at?->toISOString(),
        ]);
    }

    public function getQrCode()
    {
        $result = $this->waService->getQrCode(self::ADMIN_SESSION_ID);
        return response()->json($result);
    }
}
