<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\WhatsappNotification;
use Illuminate\Console\Command;

class GenerateWhatsappReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Whatsapp reminders for invoices due in 3 days or less';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get invoices that are not paid, not waiting for confirmation, and are due in 3 days or already past due
        $invoices = Invoice::whereIn('status', ['belum_dibayar', 'jatuh_tempo'])
            ->where('due_date', '<=', now()->addDays(3))
            ->with(['tenant', 'tenancy.room.boardingHouse'])
            ->get();

        $count = 0;
        foreach ($invoices as $invoice) {
            $tenant = $invoice->tenant;
            if (!$tenant || !$tenant->whatsapp_number) continue;

            $diffDays = now()->startOfDay()->diffInDays($invoice->due_date, false);
            $dayText = $diffDays > 0 ? "dalam {$diffDays} hari" : ($diffDays === 0 ? "HARI INI" : "TELAH LEWAT");

            // Avoid generating duplicate reminder for the same date
            $exists = WhatsappNotification::where('invoice_id', $invoice->id)
                ->where('message_type', 'pengingat_jatuh_tempo')
                ->where('scheduled_date', today())
                ->exists();

            if (!$exists) {
                WhatsappNotification::create([
                    'invoice_id' => $invoice->id,
                    'tenant_id' => $tenant->id,
                    'phone_number' => $tenant->whatsapp_number,
                    'message_type' => 'pengingat_jatuh_tempo',
                    'message_body' => "Halo {$tenant->name}, ini adalah pengingat bahwa tagihan sewa kamar Anda di {$invoice->tenancy->room->boardingHouse->name} sebesar Rp" . number_format($invoice->amount, 0, ',', '.') . " jatuh tempo $dayText (" . $invoice->due_date->format('d M Y') . "). Mohon segera lakukan pembayaran.",
                    'scheduled_date' => today(),
                    'status' => 'belum_dikirim',
                ]);
                $count++;
            }
        }

        $this->info("Generated {$count} new reminders.");
    }
}
