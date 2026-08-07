<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WaBotConversation;
use App\Models\WaSession;
use App\Services\WhatsappBotReplyService;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessWhatsappBotMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public WaBotConversation $conversation,
        public ?User $sender,
        public string $role,
        public string $text,
        public string $fromJid
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lock = Cache::lock('wa_bot_processing_'.$this->conversation->id, 110);

        if (! $lock->get()) {
            $this->release(5);

            return;
        }

        try {
            $reply = app(WhatsappBotReplyService::class)
                ->generateReply($this->conversation, $this->sender, $this->role, $this->text);

            $this->sendReply($this->fromJid, $reply);

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
        } finally {
            $lock->release();
        }
    }

    /**
     * Kirim balasan WA via WhatsappService.
     */
    protected function sendReply(string $jid, string $message): void
    {
        try {
            app(WhatsappService::class)->sendMessage(WaSession::SUPERADMIN_SESSION_ID, $jid, $message, true);
        } catch (\Exception $e) {
            Log::error('[WA Bot Queue] Failed to send reply via WA Service', [
                'to_jid' => $jid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
