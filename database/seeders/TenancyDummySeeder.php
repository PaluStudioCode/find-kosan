<?php

namespace Database\Seeders;

use App\Models\AdminWallet;
use App\Models\AdminWalletTransaction;
use App\Models\BoardingHouse;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenancy;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class TenancyDummySeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // Dapatkan kos yang dipublikasikan
        $boardingHouses = BoardingHouse::where('status', 'dipublikasikan')->get();
        if ($boardingHouses->isEmpty()) {
            $this->command->error('Tidak ada kos yang dipublikasikan. Jalankan KosPaluDummySeeder terlebih dahulu.');
            return;
        }

        // Dapatkan persentase potongan admin (fee)
        $feeSetting = Setting::where('key', 'fee_percent')->first();
        $feePercent = $feeSetting ? (int)$feeSetting->value : 5;

        $ppnSetting = Setting::where('key', 'ppn_percent')->first();
        $ppnPercent = $ppnSetting ? (int)$ppnSetting->value : 11;
        
        $pphSetting = Setting::where('key', 'pph_percent')->first();
        $pphPercent = $pphSetting ? (int)$pphSetting->value : 10;

        $this->command->info('Membuat 300 Tenant dummy asli Indonesia...');
        $tenants = [];
        
        // Agar mempercepat query mass-insert, bisa juga satu-satu tapi karena create lebih aman untuk event
        for ($i = 0; $i < 300; $i++) {
            $tenants[] = User::create([
                'name' => $faker->name(),
                'email' => 'tenant' . ($i + 1) . '@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'whatsapp_number' => '628' . mt_rand(100000000, 999999999),
                'role' => 'user',
                'status' => 'aktif',
            ]);
        }

        $this->command->info('Melakukan proses penyewaan kamar (3 Standar, 3 AC per kos)...');
        
        $tenantIndex = 0;
        $superAdmin = User::where('role', 'super_admin')->first();

        foreach ($boardingHouses as $kos) {
            $adminId = $kos->admin_id;

            // Pastikan AdminWallet ada
            $adminWallet = AdminWallet::firstOrCreate(
                ['admin_id' => $adminId],
                ['available_balance' => 0]
            );

            // Ambil 3 Kamar Standar yang masih tersedia
            $stdRooms = Room::where('boarding_house_id', $kos->id)
                ->where('name', 'LIKE', '%Standar%')
                ->where('status', 'tersedia')
                ->inRandomOrder()
                ->take(3)
                ->get();

            // Ambil 3 Kamar AC yang masih tersedia
            $acRooms = Room::where('boarding_house_id', $kos->id)
                ->where('name', 'LIKE', '%AC%')
                ->where('status', 'tersedia')
                ->inRandomOrder()
                ->take(3)
                ->get();

            $roomsToRent = $stdRooms->merge($acRooms);

            foreach ($roomsToRent as $room) {
                if ($tenantIndex >= count($tenants)) {
                    break;
                }

                $tenant = $tenants[$tenantIndex];
                
                // Ubah status kamar
                $room->update(['status' => 'terisi']);

                // Tanggal sewa: mundur 1 s/d 30 hari
                $startDate = Carbon::now()->subDays(rand(1, 30))->startOfDay();
                $endDate = $room->price_period === 'bulanan' 
                    ? $startDate->copy()->addMonth() 
                    : $startDate->copy()->addYear();

                // Buat Tenancy
                $tenancy = Tenancy::create([
                    'user_id' => $tenant->id,
                    'admin_id' => $adminId,
                    'boarding_house_id' => $kos->id,
                    'room_id' => $room->id,
                    'occupant_count' => rand(1, $room->capacity),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'aktif',
                    'created_at' => $startDate,
                    'updated_at' => $startDate,
                ]);

                $ppnAmount = ($room->price * $ppnPercent) / 100;
                $totalAmount = $room->price + $ppnAmount;

                // Buat Invoice Lunas
                $invoice = Invoice::create([
                    'tenancy_id' => $tenancy->id,
                    'user_id' => $tenant->id,
                    'admin_id' => $adminId,
                    'period_start' => $startDate,
                    'period_end' => $endDate,
                    'rent_price' => $room->price,
                    'ppn_percent' => $ppnPercent,
                    'ppn_amount' => $ppnAmount,
                    'amount' => $totalAmount,
                    'due_date' => $startDate->copy()->addDays(3),
                    'status' => 'lunas',
                    'payment_method' => 'bank_transfer',
                    'created_at' => $startDate,
                    'updated_at' => $startDate,
                ]);

                // Buat Payment
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => $tenant->id,
                    'admin_id' => $adminId,
                    'amount' => $totalAmount,
                    'payment_date' => $startDate,
                    'status' => 'diterima',
                    'note' => 'Pembayaran lunas (Dummy)',
                    'reviewed_at' => $startDate->copy()->addHours(2),
                    'reviewed_by' => $superAdmin ? $superAdmin->id : $adminId,
                    'created_at' => $startDate,
                    'updated_at' => $startDate->copy()->addHours(2),
                ]);

                // Perhitungan Fee & Saldo Dompet Pemilik
                $feeAmount = ($room->price * $feePercent) / 100;
                $netAmount = $room->price - $feeAmount;

                $adminWallet->increment('available_balance', $netAmount);

                AdminWalletTransaction::create([
                    'admin_wallet_id' => $adminWallet->id,
                    'invoice_id' => $invoice->id,
                    'type' => 'payment_credit',
                    'amount' => $netAmount,
                    'description' => 'Pendapatan sewa kamar ' . $room->room_number . ' kos ' . $kos->name,
                    'created_at' => $startDate->copy()->addHours(2),
                    'updated_at' => $startDate->copy()->addHours(2),
                ]);

                $tenantIndex++;
            }
        }
        
        // Buat penarikan dummy untuk menguji PPh dan metrik
        if (isset($adminWallet) && $adminWallet->available_balance > 10000000) {
            $this->command->info('Membuat data penarikan dummy untuk Admin Wallet...');
            $withdrawals = [
                ['amount' => 5000000, 'status' => 'selesai'],
                ['amount' => 2000000, 'status' => 'menunggu_persetujuan'],
                ['amount' => 1000000, 'status' => 'ditolak'],
            ];

            foreach ($withdrawals as $wd) {
                $pphAmount = ($wd['amount'] * $pphPercent) / 100;
                $netAmount = $wd['amount'] - $pphAmount;
                
                if ($wd['status'] === 'selesai') {
                    $adminWallet->decrement('available_balance', $wd['amount']);
                } elseif ($wd['status'] === 'menunggu_persetujuan') {
                    $adminWallet->decrement('available_balance', $wd['amount']);
                    $adminWallet->increment('pending_withdrawal_balance', $wd['amount']);
                } // jika ditolak, saldo seolah sudah dikembalikan (tidak diubah)

                $withdrawalRecord = \App\Models\WithdrawalRequest::create([
                    'admin_id' => $adminWallet->admin_id,
                    'amount' => $wd['amount'],
                    'pph_percent' => $pphPercent,
                    'pph_amount' => $pphAmount,
                    'net_amount' => $netAmount,
                    'bank_name' => 'BCA',
                    'account_number' => '1234567890',
                    'account_holder_name' => 'Pemilik Kos',
                    'status' => $wd['status'],
                    'owner_note' => 'Tarik dana untuk operasional',
                    'reviewed_by' => $wd['status'] !== 'menunggu_persetujuan' ? $superAdmin->id : null,
                    'reviewed_at' => $wd['status'] !== 'menunggu_persetujuan' ? Carbon::now()->subDays(1) : null,
                    'review_note' => $wd['status'] === 'ditolak' ? 'Nomor rekening tidak valid' : null,
                    'transferred_by' => $wd['status'] === 'selesai' ? $superAdmin->id : null,
                    'transferred_at' => $wd['status'] === 'selesai' ? Carbon::now()->subDays(1) : null,
                    'transfer_reference' => $wd['status'] === 'selesai' ? 'TRX-' . mt_rand(10000, 99999) : null,
                ]);

                if ($wd['status'] === 'selesai') {
                    AdminWalletTransaction::create([
                        'admin_wallet_id' => $adminWallet->id,
                        'withdrawal_request_id' => $withdrawalRecord->id,
                        'type' => 'withdrawal_debit',
                        'amount' => $wd['amount'],
                        'description' => "Penarikan #{$withdrawalRecord->id} berhasil ditransfer",
                    ]);
                }
            }
        }

        $this->command->info('Seeding transaksi sewa selesai. Total kamar disewa: ' . $tenantIndex);
    }
}
