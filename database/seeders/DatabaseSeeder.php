<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Rule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Bersihkan seluruh folder file yang di-upload beserta isi filenya
        $publicDirectories = ['kos_photos', 'legal_docs', 'payment_receipts', 'withdrawal-proofs'];
        foreach ($publicDirectories as $dir) {
            Storage::disk('public')->deleteDirectory($dir);
        }

        $localDirectories = ['legal_documents', 'payments', 'qris'];
        foreach ($localDirectories as $dir) {
            Storage::disk('local')->deleteDirectory($dir);
        }

        // Super Admin
        $superAdmin = User::firstOrCreate([
            'email' => 'superadmin@example.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'status' => 'aktif',
            'whatsapp_number' => null,
            'email_verified_at' => Carbon::now(),
        ]);

        // Pemilik Kos Dummy
        User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Pemilik Kos',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'aktif',
            'whatsapp_number' => "082195466654",
            'email_verified_at' => Carbon::now(),
        ]);

        User::firstOrCreate([
            'email' => 'user@example.com',
        ], [
            'name' => 'Penyewa',
            'password' => Hash::make('password'),
            'role' => 'user',
            'status' => 'aktif',
            'whatsapp_number' => null,
            'email_verified_at' => null,
        ]);

        // Master Data Facilities
        $facilities = [
            // Kos Facilities
            ['name' => 'WiFi / Internet', 'type' => 'kos', 'status' => 'aktif'],
            ['name' => 'Dapur Umum', 'type' => 'kos', 'status' => 'aktif'],
            ['name' => 'Parkir Motor', 'type' => 'kos', 'status' => 'aktif'],
            ['name' => 'Parkir Mobil', 'type' => 'kos', 'status' => 'aktif'],
            ['name' => 'Keamanan CCTV 24 Jam', 'type' => 'kos', 'status' => 'aktif'],
            ['name' => 'Ruang Santai / Tamu', 'type' => 'kos', 'status' => 'aktif'],
            ['name' => 'Kulkas Bersama', 'type' => 'kos', 'status' => 'aktif'],
            ['name' => 'Mesin Cuci Bersama', 'type' => 'kos', 'status' => 'aktif'],

            // Kamar Facilities
            ['name' => 'Kamar Mandi Dalam', 'type' => 'kamar', 'status' => 'aktif'],
            ['name' => 'AC', 'type' => 'kamar', 'status' => 'aktif'],
            ['name' => 'Kipas Angin', 'type' => 'kamar', 'status' => 'aktif'],
            ['name' => 'Kasur & Bantal', 'type' => 'kamar', 'status' => 'aktif'],
            ['name' => 'Lemari Pakaian', 'type' => 'kamar', 'status' => 'aktif'],
            ['name' => 'Meja & Kursi', 'type' => 'kamar', 'status' => 'aktif'],
            ['name' => 'Jendela / Ventilasi', 'type' => 'kamar', 'status' => 'aktif'],
            ['name' => 'Water Heater', 'type' => 'kamar', 'status' => 'aktif'],
        ];

        foreach ($facilities as $facility) {
            Facility::firstOrCreate([
                'name' => $facility['name'],
                'type' => $facility['type'],
            ], [
                'status' => $facility['status'],
            ]);
        }

        // Master Data Rules
        $rules = [
            ['name' => 'Bebas jam malam', 'is_positive' => true],
            ['name' => 'Ada jam malam', 'is_positive' => false],
            ['name' => 'Boleh pasutri', 'is_positive' => true],
            ['name' => 'Dilarang bawa anak', 'is_positive' => false],
            ['name' => 'Tamu boleh menginap', 'is_positive' => true],
            ['name' => 'Tamu dilarang menginap', 'is_positive' => false],
            ['name' => 'Boleh bawa hewan peliharaan', 'is_positive' => true],
            ['name' => 'Dilarang bawa hewan peliharaan', 'is_positive' => false],
            ['name' => 'Dilarang merokok di kamar', 'is_positive' => false],
            ['name' => 'Wajib menjaga kebersihan', 'is_positive' => true],
            ['name' => 'Dilarang berisik setelah jam 10 malam', 'is_positive' => false],
        ];

        foreach ($rules as $rule) {
            Rule::firstOrCreate([
                'name' => $rule['name'],
            ], [
                'is_positive' => $rule['is_positive'],
            ]);
        }

        // Pastikan Data Wilayah Laravolt sudah di-seed
        if (\Illuminate\Support\Facades\DB::table('cities')->count() == 0) {
            $this->command->info('Melakukan seeding data wilayah Laravolt Indonesia...');
            \Illuminate\Support\Facades\Artisan::call('laravolt:indonesia:seed');
            $this->command->info('Seeding data wilayah selesai!');
        }

        // Panggil seeder 50 Kos Dummy Palu
        $this->call([
            KosPaluDummySeeder::class,
        ]);
    }
}
