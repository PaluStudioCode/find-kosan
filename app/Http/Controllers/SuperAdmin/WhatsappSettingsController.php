<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\WhatsappNumber;
use App\Http\Controllers\Controller;
use App\Models\WaSession;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class WhatsappSettingsController extends Controller
{
    protected WhatsappService $waService;

    public function __construct(WhatsappService $waService)
    {
        $this->waService = $waService;
    }

    public function startSession()
    {
        $result = $this->waService->startSession(WaSession::SUPERADMIN_SESSION_ID);

        return response()->json($result);
    }

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

        // Normalize to 62xxx before sending to wa-service for consistency.
        $normalized = WhatsappNumber::normalize($validated['phone_number']);
        $result = $this->waService->startSessionWithPairingCode(WaSession::SUPERADMIN_SESSION_ID, $normalized);

        return response()->json($result);
    }

    public function stopSession()
    {
        $result = $this->waService->stopSession(WaSession::SUPERADMIN_SESSION_ID);

        return response()->json($result);
    }

    public function getStatus()
    {
        $liveStatus = $this->waService->getStatus(WaSession::SUPERADMIN_SESSION_ID);
        $session = WaSession::superAdminSession()->first();

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

    public function getQrCode()
    {
        $result = $this->waService->getQrCode(WaSession::SUPERADMIN_SESSION_ID);

        return response()->json($result);
    }

    public function testMessage(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^(\+62|62|08)\d{8,13}$/'],
            'type' => ['nullable', 'string', 'in:jatuh_tempo,umum'],
        ]);

        $normalized = WhatsappNumber::normalize($request->phone);

        if (! WhatsappNumber::isValid($normalized)) {
            return response()->json([
                'success' => false,
                'error' => 'Nomor WhatsApp tidak valid.',
            ], 422);
        }

        $type = $request->query('type', 'jatuh_tempo');

        if ($type === 'jatuh_tempo') {
            $message = "Halo Uji Coba, ini adalah pengingat simulasi bahwa tagihan sewa kamar Anda di Kos Dummy sebesar Rp1.500.000 jatuh tempo HARI INI (" . today()->format('d M Y') . ").\n\nSilakan abaikan pesan ini, ini hanya testing dari SuperAdmin.";
        } else {
            $message = "Halo! Ini adalah pesan uji coba (testing) dari sistem CariKosanMu. Jika Anda menerima pesan ini, artinya Gateway WhatsApp beroperasi dengan baik.";
        }

        // Send immediately bypassing queue
        $result = $this->waService->sendMessage(WaSession::SUPERADMIN_SESSION_ID, $normalized, $message);

        return response()->json([
            'success' => $result['status'] ?? false,
            'message_type' => $type,
            'phone' => $normalized,
            'wa_api_response' => $result,
            'note' => 'Pesan dikirim secara instan (bypassing queue)'
        ]);
    }
}
