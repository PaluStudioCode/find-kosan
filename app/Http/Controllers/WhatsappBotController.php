<?php

namespace App\Http\Controllers;

use App\Helpers\WhatsappNumber;
use App\Models\ActivityLog;
use App\Models\WaBotConversation;
use App\Models\WaBotMessage;
use App\Models\WaSession;
use App\Services\WhatsappBotContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Class WhatsappBotController
 *
 * Menerima webhook dari wa-service ketika ada pesan WhatsApp masuk
 * ke nomor SuperAdmin (session admin_id=0).
 *
 * Support dua format JID pengirim:
 *  - @s.whatsapp.net → nomor telepon eksplisit (kontak tersimpan/terdaftar)
 *  - @lid             → Linked Identity (nomor tidak di kontak / privasi WA)
 *
 * Alur:
 *  wa-service (Baileys messages.upsert)
 *    -> POST /wa/webhook/incoming
 *    -> handleIncoming()
 *    -> identify sender (berbasis JID) + rate-limit + upsert conversation
 *    -> simpan pesan user
 *    -> call LLM dengan function calling + reply WA (ke JID asli)
 */
class WhatsappBotController extends Controller
{
    public function __construct(protected WhatsappBotContextService $contextService)
    {
    }

    /**
     * Handle incoming WhatsApp message forwarded by wa-service.
     *
     * Payload dari wa-service:
     *   - from: string (identifier pengirim tanpa @suffix)
     *   - from_jid: string (JID asli lengkap, mis. "628xxx@s.whatsapp.net" atau "173xxx@lid")
     *   - text: string (isi pesan)
     *   - messageId: string
     *   - timestamp: int (unix)
     *   - sessionId: string|int (admin_id session, "0" untuk SuperAdmin)
     */
    public function handleIncoming(Request $request): JsonResponse
    {
        // 1. Verifikasi signature
        $signature = $request->header('X-WA-Signature');
        $expectedKey = config('services.wa_service.api_key');
        if (! $expectedKey || ! hash_equals($expectedKey, (string) $signature)) {
            Log::warning('[WA Bot] Unauthorized webhook', [
                'ip' => $request->ip(),
                'signature_present' => ! empty($signature),
            ]);
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        // 2. Validasi payload
        $validated = $request->validate([
            'from' => ['required', 'string'],
            'from_jid' => ['nullable', 'string'],
            'text' => ['required', 'string'],
            'messageId' => ['nullable', 'string'],
            'timestamp' => ['nullable', 'integer'],
            'sessionId' => ['nullable', 'string'],
        ]);

        // 3. Cek kill switch global
        if (! config('services.wa_bot.enabled', true)) {
            return response()->json(['success' => true, 'message' => 'Bot disabled']);
        }

        // 4. Tentukan JID pengirim (fallback ke 'from' jika from_jid tidak ada)
        $fromJid = $validated['from_jid'] ?? null;
        if (! $fromJid) {
            // Fallback: asumsikan from adalah nomor telepon → bangun JID
            $fromJid = $validated['from'];
            if (! str_contains($fromJid, '@')) {
                $fromJid .= '@s.whatsapp.net';
            }
        }

        Log::info('[WA Bot] Webhook received', [
            'from_jid' => $fromJid,
            'text_preview' => substr($validated['text'], 0, 50),
        ]);

        // 5. Anti-loop: skip jika pengirim = nomor SuperAdmin sendiri
        if ($this->isSuperAdminOwnJid($fromJid)) {
            return response()->json(['success' => true, 'message' => 'Self-message skipped']);
        }

        // 6. Rate-limit per JID (20 pesan/jam)
        $rateLimitKey = 'wa_bot_rate_' . $fromJid;
        $maxAttempts = (int) config('services.wa_bot.rate_limit_per_hour', 20);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            // Hanya balas 1x saat pertama kali hit limit, lalu silent
            $notifiedKey = 'wa_bot_ratelimited_' . $fromJid;
            if (! Cache::has($notifiedKey)) {
                Cache::put($notifiedKey, true, now()->addHour());
                $this->sendReply($fromJid, 'Anda telah mencapai batas ' . $maxAttempts . ' pesan/jam. Silakan coba lagi nanti ya. 😊');
                Log::info('[WA Bot] Rate limit hit, notified', ['from_jid' => $fromJid]);
            }
            return response()->json(['success' => true, 'message' => 'Rate limited']);
        }
        RateLimiter::hit($rateLimitKey, 3600); // 1 jam window

        // 7. Identifikasi sender berbasis JID
        $identified = $this->contextService->identifySender($fromJid);
        $sender = $identified['user'];
        $role = $identified['role'];
        $phone = $identified['phone'];

        // 8. Upsert conversation (berbasis from_jid)
        $conversation = $this->contextService->upsertConversation($fromJid, $sender, $role, $phone);

        // Cek opt-out per conversation
        if (! $conversation->is_bot_enabled) {
            return response()->json(['success' => true, 'message' => 'Bot disabled for this number']);
        }

        // 9. Command khusus: /reset
        if (strtolower(trim($validated['text'])) === '/reset') {
            $conversation->messages()->delete();
            $conversation->update(['context_summary' => null, 'total_messages' => 0]);
            ActivityLog::create([
                'action' => 'bot.command_reset',
                'metadata' => ['from_jid' => $fromJid, 'conversation_id' => $conversation->id],
            ]);
            $this->sendReply($fromJid, '✅ Riwayat percakapan Anda telah direset. Ada yang bisa saya bantu?');
            return response()->json(['success' => true, 'message' => 'Reset done']);
        }

        // 10. Simpan pesan user
        WaBotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $validated['text'],
        ]);
        $conversation->touchActivity();

        // 11. Log inbound
        ActivityLog::create([
            'user_id' => $sender?->id,
            'action' => 'bot.inbound',
            'subject_type' => WaBotConversation::class,
            'subject_id' => $conversation->id,
            'metadata' => [
                'from_jid' => $fromJid,
                'role' => $role,
                'text' => $validated['text'],
                'message_id' => $validated['messageId'] ?? null,
            ],
        ]);

        // 12. Generate reply via LLM (function calling) & kirim via WA
        $reply = app(\App\Services\WhatsappBotReplyService::class)
            ->generateReply($conversation, $sender, $role, $validated['text']);

        $this->sendReply($fromJid, $reply);

        // 13. Log outbound
        ActivityLog::create([
            'user_id' => $sender?->id,
            'action' => 'bot.reply_sent',
            'subject_type' => WaBotConversation::class,
            'subject_id' => $conversation->id,
            'metadata' => [
                'to_jid' => $fromJid,
                'role' => $role,
                'reply_length' => strlen($reply),
                'reply_preview' => substr($reply, 0, 100),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message processed & replied',
            'data' => [
                'from_jid' => $fromJid,
                'role' => $role,
                'conversation_id' => $conversation->id,
                'sender_name' => $sender?->name,
                'reply' => $reply,
            ],
        ]);
    }

    /**
     * Kirim balasan WA via WhatsappService (session SuperAdmin admin_id=0).
     *
     * @param string $jid JID asli pengirim (mis. "628xxx@s.whatsapp.net" atau "173xxx@lid")
     */
    protected function sendReply(string $jid, string $message): void
    {
        try {
            // useRawJid=true → kirim ke JID apa adanya (penting untuk @lid)
            app(\App\Services\WhatsappService::class)->sendMessage(WaSession::SUPERADMIN_SESSION_ID, $jid, $message, true);
        } catch (\Exception $e) {
            Log::error('[WA Bot] Failed to send reply', [
                'to_jid' => $jid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cek apakah JID = nomor SuperAdmin sendiri (anti-loop).
     * Bandingkan berdasarkan nomor telepon (hanya @s.whatsapp.net yang bisa).
     */
    protected function isSuperAdminOwnJid(string $jid): bool
    {
        // @lid tidak mungkin = nomor SuperAdmin sendiri (format berbeda)
        if (str_ends_with($jid, '@lid')) {
            return false;
        }

        if (! str_ends_with($jid, '@s.whatsapp.net')) {
            return false;
        }

        $ownNumber = WaSession::superAdminSession()
            ->where('status', 'connected')
            ->value('phone_number');

        if (! $ownNumber) {
            return false;
        }

        $ownNormalized = WhatsappNumber::normalize($ownNumber);
        $senderNormalized = WhatsappNumber::normalize(str_replace('@s.whatsapp.net', '', $jid));

        return $ownNormalized === $senderNormalized;
    }
}
