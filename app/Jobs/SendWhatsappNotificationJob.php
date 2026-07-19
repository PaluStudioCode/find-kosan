<?php

namespace App\Jobs;

use App\Models\WhatsappNotification;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsappNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notification;

    /**
     * Create a new job instance.
     */
    public function __construct(WhatsappNotification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsappService $whatsappService): void
    {
        if ($this->notification->status !== 'belum_dikirim') {
            return; // Already processed
        }

        $result = $whatsappService->sendMessage(
            $this->notification->phone_number,
            $this->notification->message_body
        );

        if ($result['status']) {
            $this->notification->update([
                'status' => 'terkirim',
                'sent_at' => now(),
                'gateway_response' => $result['response'] ?? null,
            ]);
        } else {
            $this->notification->update([
                'status' => 'gagal',
                'failed_reason' => $result['reason'] ?? 'Unknown error',
            ]);
        }
    }
}
