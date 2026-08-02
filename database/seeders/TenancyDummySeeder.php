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

                // Buat Invoice Lunas
                $invoice = Invoice::create([
                    'tenancy_id' => $tenancy->id,
                    'user_id' => $tenant->id,
                    'admin_id' => $adminId,
                    'period_start' => $startDate,
                    'period_end' => $endDate,
                    'amount' => $room->price,
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
                    'amount' => $room->price,
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
        
        $this->command->info('Seeding transaksi sewa selesai. Total kamar disewa: ' . $tenantIndex);
    }
}
