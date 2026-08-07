<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WaBotConversation;
use App\Models\WaSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsappBotMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Waktu maksimal job boleh berjalan (detik).
     * Set agak panjang (120 detik) untuk memberi waktu LLM memproses.
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public WaBotConversation $conversation,
        public ?User $sender,
        public string $role,
        public string $text,
        public string $fromJid
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Generate reply via LLM (function calling)
            $reply = app(\App\Services\WhatsappBotReplyService::class)
                ->generateReply($this->conversation, $this->sender, $this->role, $this->text);

            // Kirim balasan WA (ke JID asli)
            $this->sendReply($this->fromJid, $reply);

            // Log outbound
            ActivityLog::create([
                'user_id' => $this->sender?->id,
                'action' => 'bot.reply_sent',
                'subject_type' => WaBotConversation::class,
                'subject_id' => $this->conversation->id,
                'metadata' => [
                    'to_jid' => $this->fromJid,
                    'role' => $this->role,
                    'reply_length' => strlen($reply),
                    'reply_preview' => substr($reply, 0, 100),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[WA Bot Queue] Failed processing message', [
                'conversation_id' => $this->conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Kirim balasan WA via WhatsappService.
     */
    protected function sendReply(string $jid, string $message): void
    {
        try {
            app(\App\Services\WhatsappService::class)->sendMessage(WaSession::SUPERADMIN_SESSION_ID, $jid, $message, true);
        } catch (\Exception $e) {
            Log::error('[WA Bot Queue] Failed to send reply via WA Service', [
                'to_jid' => $jid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
