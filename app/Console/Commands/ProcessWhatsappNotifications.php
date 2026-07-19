<?php

namespace App\Console\Commands;

use App\Models\WhatsappNotification;
use App\Jobs\SendWhatsappNotificationJob;
use Illuminate\Console\Command;

class ProcessWhatsappNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending scheduled WhatsApp notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $notifications = WhatsappNotification::where('status', 'belum_dikirim')
            ->where('scheduled_date', '<=', today())
            ->get();

        $this->info("Found {$notifications->count()} notifications to process.");

        foreach ($notifications as $notification) {
            SendWhatsappNotificationJob::dispatch($notification);
        }

        $this->info("All pending notifications have been dispatched.");
    }
}
