<?php

namespace App\Http\Controllers;

use App\Helpers\WhatsappNumber;
use App\Models\ActivityLog;
use App\Models\WaBotConversation;
use App\Models\WaBotMessage;
use App\Models\WaSession;
use App\Services\WhatsappBotService;
use App\Services\WhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class WhatsappBotController extends Controller
{
    public function __construct(
        protected WhatsappBotService $botService,
        protected WhatsappService $waService,
    ) {}

    public function handleIncoming(Request $request): JsonResponse
    {
        $signature = $request->header('X-WA-Signature');
        $expectedKey = config('services.wa_service.api_key');
        if (! $expectedKey || ! hash_equals($expectedKey, (string) $signature)) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'from' => ['required', 'string'],
            'from_jid' => ['nullable', 'string'],
            'text' => ['required', 'string'],
            'messageId' => ['nullable', 'string'],
            'timestamp' => ['nullable', 'integer'],
            'sessionId' => ['nullable', 'string'],
        ]);

        if (! config('services.wa_bot.enabled', true)) {
            return response()->json(['success' => true, 'message' => 'Bot disabled']);
        }

        $fromJid = $validated['from_jid'] ?? null;
        if (! $fromJid) {
            $fromJid = $validated['from'];
            if (! str_contains($fromJid, '@')) {
                $fromJid .= '@s.whatsapp.net';
            }
        }

        if ($this->isSuperAdminOwnJid($fromJid)) {
            return response()->json(['success' => true, 'message' => 'Self-message skipped']);
        }

        $rateLimitKey = 'wa_bot_rate_'.$fromJid;
        $maxAttempts = (int) config('services.wa_bot.rate_limit_per_hour', 20);
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            return response()->json(['success' => true, 'message' => 'Rate limited']);
        }
        RateLimiter::hit($rateLimitKey, 3600);

        $identified = $this->botService->identifySender($fromJid);
        $sender = $identified['user'];
        $role = $identified['role'];
        $phone = $identified['phone'];

        $conversation = $this->botService->upsertConversation($fromJid, $sender, $role, $phone);

        if (! $conversation->is_bot_enabled) {
            return response()->json(['success' => true, 'message' => 'Bot disabled for this number']);
        }

        if (strtolower(trim($validated['text'])) === '/reset') {
            $conversation->messages()->delete();
            $conversation->update(['context_summary' => null, 'total_messages' => 0]);
            $this->sendReply($fromJid, '✅ Riwayat percakapan Anda telah direset. Ada yang bisa saya bantu?');

            return response()->json(['success' => true, 'message' => 'Reset done']);
        }

        WaBotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $validated['text'],
        ]);
        $conversation->touchActivity();

        ActivityLog::create([
            'user_id' => $sender?->id,
            'action' => 'bot.inbound',
            'subject_type' => WaBotConversation::class,
            'subject_id' => $conversation->id,
            'metadata' => [
                'from_jid' => $fromJid,
                'role' => $role,
                'text' => $validated['text'],
            ],
        ]);

        if (function_exists('fastcgi_finish_request')) {
            response()->json([
                'success' => true,
                'message' => 'Message accepted',
            ])->send();
            fastcgi_finish_request();
        }

        $this->processAndReply($conversation, $sender, $role, $validated['text'], $fromJid);

        return response()->json(['success' => true, 'message' => 'Processed']);
    }

    protected function processAndReply(
        WaBotConversation $conversation,
        $sender,
        string $role,
        string $text,
        string $fromJid,
    ): void {
        $lockName = 'wa_bot_conv_'.$conversation->id;
        $lockResult = DB::selectOne('SELECT GET_LOCK(?, 30) as acquired', [$lockName]);

        if (! $lockResult || ! $lockResult->acquired) {
            Log::warning('[WA Bot] Could not acquire lock', ['conversation_id' => $conversation->id]);
            $this->sendReply($fromJid, 'Pesan Anda sedang diproses, mohon tunggu sebentar.');

            return;
        }

        try {
            $reply = $this->botService->generateReply($conversation, $sender, $role, $text);
            $this->sendReply($fromJid, $reply);

            ActivityLog::create([
                'user_id' => $sender?->id,
                'action' => 'bot.reply_sent',
                'subject_type' => WaBotConversation::class,
                'subject_id' => $conversation->id,
                'metadata' => [
                    'to_jid' => $fromJid,
                    'role' => $role,
                    'reply_length' => strlen($reply),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[WA Bot] Process failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
            $this->sendReply($fromJid, 'Maaf, terjadi kesalahan. Silakan coba lagi nanti.');
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }

    protected function sendReply(string $jid, string $message): void
    {
        try {
            $this->waService->sendMessage(WaSession::SUPERADMIN_SESSION_ID, $jid, $message, true);
        } catch (\Exception $e) {
            Log::error('[WA Bot] Failed to send reply', [
                'to_jid' => $jid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function isSuperAdminOwnJid(string $jid): bool
    {
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

        return WhatsappNumber::normalize($ownNumber) === WhatsappNumber::normalize(str_replace('@s.whatsapp.net', '', $jid));
    }
}
