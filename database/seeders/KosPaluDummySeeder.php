<?php

namespace Database\Seeders;

use App\Models\BoardingHouse;
use App\Models\BoardingHouseLegalDocument;
use App\Models\BoardingHousePhoto;
use App\Models\Facility;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KosPaluDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();
        $admin = User::where('role', 'admin')->first();

        if (!$superAdmin || !$admin) {
            $this->command->error('Super Admin atau Admin tidak ditemukan. Harap jalankan DatabaseSeeder terlebih dahulu.');
            return;
        }

        $kosFacilities = Facility::where('type', 'kos')->pluck('id');
        $roomFacilities = Facility::where('type', 'kamar')->pluck('id');
        $kosRules = \App\Models\Rule::pluck('id');

        $faker = \Faker\Factory::create('id_ID');

        // Ambil Data Kota Palu dari Laravolt Indonesia
        $cityPalu = DB::table('cities')->where('name', 'LIKE', '%PALU%')->first();
        if (!$cityPalu) {
            $this->command->error('Data Kota Palu tidak ditemukan di tabel cities Laravolt.');
            return;
        }

        // Daftar keyword untuk foto realistis menggunakan LoremFlickr (selalu berhasil dan sesuai keyword)
        $photoKeywords = [
            'utama' => 'house,exterior',
            'kamar' => 'bedroom,interior',
            'kamar_mandi' => 'bathroom,shower',
            'fasilitas' => 'kitchen,livingroom',
            'lingkungan' => 'street,neighborhood'
        ];

        // Zona daratan Kota Palu (Super Aman, Dijamin tidak masuk laut)
        // Silae dihapus, tapi Palu Barat (Balaroa, Kamonji, Ujuna) tetap ada (aman di daratan dalam).
        $landZones = [
            // Zona 1: Palu Barat Bagian Dalam (Balaroa, Kamonji) - Menjauhi pesisir Teluk
            ['lat_min' => -0.9050, 'lat_max' => -0.8900, 'lng_min' => 119.8350, 'lng_max' => 119.8550],
            // Zona 2: Tatura, Pusat Kota (Palu Selatan Atas)
            ['lat_min' => -0.9000, 'lat_max' => -0.8900, 'lng_min' => 119.8600, 'lng_max' => 119.8900],
            // Zona 3: Tondo, Mantikulore (Timur/Utara, area kampus)
            ['lat_min' => -0.8600, 'lat_max' => -0.8300, 'lng_min' => 119.8850, 'lng_max' => 119.9100],
        ];

        for ($i = 1; $i <= 50; $i++) {
            // Pilih salah satu zona daratan secara acak
            $zone = $landZones[array_rand($landZones)];
            
            // Generate koordinat acak di dalam zona terpilih
            $lat = $zone['lat_min'] + (mt_rand(0, 10000) / 10000) * ($zone['lat_max'] - $zone['lat_min']);
            $lng = $zone['lng_min'] + (mt_rand(0, 10000) / 10000) * ($zone['lng_max'] - $zone['lng_min']);

            // Ambil Kecamatan dan Kelurahan acak di Kota Palu dari Laravolt
            $district = DB::table('districts')->where('city_code', $cityPalu->code)->inRandomOrder()->first();
            $village = DB::table('villages')->where('district_code', $district->code)->inRandomOrder()->first();

            $areaName = ucwords(strtolower($district->name));
            $name = 'Kos ' . $faker->firstName() . ' ' . $areaName;

            $kos = BoardingHouse::create([
                'admin_id' => $admin->id,
                'name' => $name,
                'description' => 'Kos nyaman dan strategis di area ' . $areaName . '. Dilengkapi dengan berbagai fasilitas pendukung untuk kenyamanan penghuni.',
                'address' => 'Jl. ' . $faker->streetName() . ' No. ' . rand(1, 100),
                'public_contact_name' => $admin->name,
                'public_contact_whatsapp_number' => '08' . mt_rand(1000000000, 9999999999),
                'city' => ucwords(strtolower($cityPalu->name)),
                'district' => ucwords(strtolower($district->name)),
                'subdistrict' => ucwords(strtolower($village->name)),
                'latitude' => $lat,
                'longitude' => $lng,
                'status' => 'dipublikasikan',
                'verified_at' => Carbon::now(),
                'verified_by' => $superAdmin->id,
            ]);

            // Attach Kos Facilities
            if ($kosFacilities->count() > 0) {
                $kos->facilities()->attach($kosFacilities->random(rand(3, 6)));
            }

            // Attach Rules
            if ($kosRules->count() > 0) {
                $kos->rules()->attach($kosRules->random(rand(3, 5)));
            }

            // --- SPESIFIKASI KAMAR STANDAR (SAMA UNTUK 5 KAMAR) ---
            $stdPrice = rand(5, 8) * 100000;
            $stdFacilities = $roomFacilities->count() > 0 ? $roomFacilities->random(rand(2, 4)) : [];
            for ($r = 1; $r <= 5; $r++) {
                $room = Room::create([
                    'boarding_house_id' => $kos->id,
                    'name' => 'Kamar Standar',
                    'room_number' => 'STD-' . str_pad($r, 2, '0', STR_PAD_LEFT),
                    'description' => 'Kamar standar non-AC yang nyaman dan sirkulasi udara baik.',
                    'price' => $stdPrice,
                    'price_period' => 'bulanan',
                    'capacity' => 1,
                    'status' => 'tersedia',
                ]);
                if (!empty($stdFacilities)) {
                    $room->facilities()->attach($stdFacilities);
                }
            }

            // --- SPESIFIKASI KAMAR AC (SAMA UNTUK 5 KAMAR) ---
            $acPrice = rand(10, 15) * 100000;
            $acFacilities = $roomFacilities->count() > 0 ? $roomFacilities->random(rand(4, 6)) : [];
            for ($r = 1; $r <= 5; $r++) {
                $room = Room::create([
                    'boarding_house_id' => $kos->id,
                    'name' => 'Kamar AC',
                    'room_number' => 'VIP-' . str_pad($r, 2, '0', STR_PAD_LEFT),
                    'description' => 'Kamar full AC dengan fasilitas lengkap dan kamar mandi dalam.',
                    'price' => $acPrice,
                    'price_period' => 'bulanan',
                    'capacity' => 2,
                    'status' => 'tersedia',
                ]);
                if (!empty($acFacilities)) {
                    $room->facilities()->attach($acFacilities);
                }
            }

            // Create 10 Photos
            $categories = ['utama', 'kamar', 'kamar_mandi', 'fasilitas', 'lingkungan'];
            for ($p = 1; $p <= 10; $p++) {
                $cat = $p === 1 ? 'utama' : $categories[array_rand($categories)];
                // Menggunakan LoremFlickr dengan parameter lock (kombinasi unik dari i dan p) agar gambar tidak error dan selalu unik
                $lockId = ($i * 10) + $p;
                $photoUrl = 'https://loremflickr.com/800/600/' . $photoKeywords[$cat] . '?lock=' . $lockId;

                BoardingHousePhoto::create([
                    'boarding_house_id' => $kos->id,
                    'file_path' => $photoUrl,
                    'category' => $cat,
                    'is_primary' => ($p === 1),
                    'sort_order' => $p,
                ]);
            }

            // Create Legal Documents
            BoardingHouseLegalDocument::create([
                'boarding_house_id' => $kos->id,
                'document_type' => 'identitas_pemilik_pengelola',
                'document_name' => 'KTP Pemilik',
                'document_number' => '72710' . mt_rand(10000000000, 99999999999),
                'file_path' => 'dummy/legal/ktp.pdf',
                'status' => 'valid',
                'review_note' => 'KTP Valid',
                'reviewed_by' => $superAdmin->id,
                'reviewed_at' => Carbon::now(),
            ]);
            BoardingHouseLegalDocument::create([
                'boarding_house_id' => $kos->id,
                'document_type' => 'bukti_kepemilikan_pengelolaan',
                'document_name' => 'Sertifikat Hak Milik (SHM)',
                'document_number' => 'SHM-' . mt_rand(1000, 9999),
                'file_path' => 'dummy/legal/shm.pdf',
                'status' => 'valid',
                'review_note' => 'Dokumen asli dan sesuai lokasi',
                'reviewed_by' => $superAdmin->id,
                'reviewed_at' => Carbon::now(),
            ]);
        }

        $this->command->info('Berhasil menambahkan 50 data Kos Dummy di Kota Palu (Data Daerah Real dari Laravolt, Spesifikasi Kamar Seragam, Foto Realistis)!');
    }
}
