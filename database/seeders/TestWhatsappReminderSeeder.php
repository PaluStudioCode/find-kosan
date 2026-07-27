<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Tenancy;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TestWhatsappReminderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari satu tenancy yang sudah aktif atau memiliki invoice lunas
        $tenancy = Tenancy::where('status', 'aktif')->with('room')->first();

        if (!$tenancy) {
            $this->command->warn('Tidak ada data sewa (Tenancy) yang aktif. Silakan buat data sewa terlebih dahulu.');
            return;
        }

        // Hapus tagihan belum dibayar yang mungkin sudah ada agar tidak ganda
        Invoice::where('tenancy_id', $tenancy->id)
            ->where('status', 'belum_dibayar')
            ->delete();

        $uniqueAdd = rand(1, 100);
        
        $invoice = Invoice::create([
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenancy->user_id,
            'admin_id' => $tenancy->admin_id,
            'period_start' => today()->addMonths($uniqueAdd),
            'period_end' => today()->addMonths($uniqueAdd + 1),
            'amount' => $tenancy->room->price ?? 500000,
            'due_date' => today(), // Jatuh tempo HARI INI
            'status' => 'belum_dibayar',
        ]);

        $this->command->info('Berhasil membuat tagihan testing dengan ID: ' . $invoice->id);
        $this->command->info('Jatuh tempo diatur pada: HARI INI (' . today()->format('d M Y') . ')');
        $this->command->info('Silakan jalankan: php artisan whatsapp:reminders');
    }
}
