<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Facility;
use App\Models\BoardingHouse;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Super Admin
        $superAdmin = User::firstOrCreate([
            'email' => 'admin@kosonline.test',
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
            'email' => 'owner@kosonline.test',
        ], [
            'name' => 'Pemilik Kos',
            'password' => Hash::make('password'),
            'role' => 'pemilik_kos',
            'status' => 'aktif',
            'whatsapp_number' => '628222222222',
            'email_verified_at' => Carbon::now(),
        ]);

        // Penyewa Dummy
        User::firstOrCreate([
            'email' => 'tenant@kosonline.test',
        ], [
            'name' => 'Penyewa Kos',
            'password' => Hash::make('password'),
            'role' => 'penyewa',
            'status' => 'aktif',
            'whatsapp_number' => '628333333333',
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
    }
}
