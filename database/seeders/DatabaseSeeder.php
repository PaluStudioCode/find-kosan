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
        // Bersihkan folder file yang di-upload agar tidak menumpuk (Orphaned files)
        $directoriesToClean = ['kos_photos', 'legal_docs', 'payment_receipts'];
        foreach ($directoriesToClean as $dir) {
            Storage::disk('public')->deleteDirectory($dir);
            Storage::disk('public')->makeDirectory($dir);

            // Fix Docker permission issue: pastikan web server bisa tulis & baca walau dibuat oleh Root
            $path = storage_path('app/public/' . $dir);
            if (file_exists($path)) {
                @chmod($path, 0777);
            }
        }

        // Super Admin
        $superAdmin = User::firstOrCreate([
            'email' => 'superadmin@example.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'status' => 'aktif',
            'whatsapp_number' => '628111111111',
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
            'whatsapp_number' => '628222222222',
            'email_verified_at' => Carbon::now(),
        ]);

        User::firstOrCreate([
            'email' => 'user@example.com',
        ], [
            'name' => 'Penyewa',
            'password' => Hash::make('password'),
            'role' => 'user',
            'status' => 'aktif',
            'whatsapp_number' => '085151246624',
            'email_verified_at' => Carbon::now(),
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
    }
}
