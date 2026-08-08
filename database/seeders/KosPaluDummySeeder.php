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
use Illuminate\Support\Facades\Storage;

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

        $this->command->info('Memeriksa ketersediaan file fisik dokumen legal dummy di dummy_data...');
        $dummyPdfContent = "%PDF-1.4\n%Dummy PDF File\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n5 0 obj\n<< /Length 44 >>\nstream\nBT /F1 24 Tf 100 700 Td (Dummy Document) Tj ET\nendstream\nendobj\nxref\n0 6\n0000000000 65535 f\n0000000044 00000 n\n0000000093 00000 n\n0000000150 00000 n\n0000000258 00000 n\n0000000346 00000 n\ntrailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n441\n%%EOF";
        
        $ktpSourceJpg = base_path('dummy_data/documents/Ktp.jpg');
        $shmSourceJpg = base_path('dummy_data/documents/shm.jpg');

        $ktpPath = 'legal_documents/dummy_ktp.pdf';
        $shmPath = 'legal_documents/dummy_shm.pdf';

        if (file_exists($ktpSourceJpg)) {
            $ktpPath = 'legal_documents/dummy_ktp.jpg';
            Storage::disk('local')->put($ktpPath, file_get_contents($ktpSourceJpg));
        } elseif (!Storage::disk('local')->exists('legal_documents/dummy_ktp.pdf')) {
            Storage::disk('local')->put('legal_documents/dummy_ktp.pdf', str_replace('(Dummy Document)', '(Dummy KTP File)', $dummyPdfContent));
        }

        if (file_exists($shmSourceJpg)) {
            $shmPath = 'legal_documents/dummy_shm.jpg';
            Storage::disk('local')->put($shmPath, file_get_contents($shmSourceJpg));
        } elseif (!Storage::disk('local')->exists('legal_documents/dummy_shm.pdf')) {
            Storage::disk('local')->put('legal_documents/dummy_shm.pdf', str_replace('(Dummy Document)', '(Dummy Sertifikat SHM)', $dummyPdfContent));
        }

        $this->command->info('Memeriksa foto-foto dummy di dummy_data...');
        $photoCategories = ['bangunan_depan', 'dalam_kamar', 'kamar_mandi', 'ruang_tamu', 'dapur', 'area_parkir', 'fasilitas_umum', 'lainnya'];
        
        $prefixMap = [
            'tampak-depan' => 'bangunan_depan',
            'dalam-kamar' => 'dalam_kamar',
            'kamar-mandi' => 'kamar_mandi',
            'ruang-tamu' => 'ruang_tamu',
            'dapur' => 'dapur',
            'parkiran' => 'area_parkir',
            'fasilitas-umum' => 'fasilitas_umum',
            'lainnya' => 'lainnya'
        ];

        $availablePhotos = [];
        foreach ($photoCategories as $cat) {
            $availablePhotos[$cat] = [];
        }

        $dummyPhotosPath = base_path('dummy_data/photos');
        if (is_dir($dummyPhotosPath)) {
            $files = scandir($dummyPhotosPath);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                foreach ($prefixMap as $prefix => $cat) {
                    if (str_starts_with($file, $prefix)) {
                        $sourcePath = $dummyPhotosPath . '/' . $file;
                        $filename = 'kos_photos/dummy_' . $file;
                        Storage::disk('public')->put($filename, file_get_contents($sourcePath));
                        $availablePhotos[$cat][] = '/storage/' . $filename;
                        break;
                    }
                }
            }
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
            'bangunan_depan' => 'house,exterior',
            'dalam_kamar' => 'bedroom,interior',
            'kamar_mandi' => 'bathroom,shower',
            'ruang_tamu' => 'livingroom,sofa',
            'dapur' => 'kitchen,stove',
            'area_parkir' => 'parking,garage',
            'fasilitas_umum' => 'kitchen,livingroom',
            'lainnya' => 'furniture,house'
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

        // Daftar fasilitas umum / landmark di Kota Palu untuk referensi deskripsi kos.
        $paluLandmarks = [
            // Pendidikan
            'Universitas Tadulako (Untad)', 'UIN Datokarama Palu', 'Universitas Alkhairaat',
            'Politeknik Palu', 'STAIN Palu', 'Kampus UNISMUH Palu',
            // Kesehatan
            'RSUD Undata', 'RS Anutapura', 'RS Bhayangkara Palu', 'RSI Palu',
            // Pasar & Pusat Belanja
            'Pasar Mal Palu', 'Pasar Manondo', 'Pasar Cemara', 'Pasar Tawaeli',
            'Mall Tatura', 'Palu Grand Mall', 'Transmart Palu', 'Ramayana Palu',
            // Transportasi
            'Bandara Mutiara SIS Al-Jufrie', 'Pelabuhan Pantoloan', 'Terminal Type A Palu',
            // Pemerintahan & Tempat Ibadah
            'Balaikota Palu', 'Masjid Raya Nur Al-Ikhlas', 'Masjid Agung Al-Khairaat',
            // Wisata
            'Pantai Talise', 'Jembatan Palu IV', 'Anjungan Palu', 'Tugu Rajawali',
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

            // Pilih 1-2 landmark acak dari daftar fasilitas umum Kota Palu
            $selectedLandmarks = array_rand(array_flip($paluLandmarks), rand(1, 2));
            if (!is_array($selectedLandmarks)) {
                $selectedLandmarks = [$selectedLandmarks];
            }
            $landmarkText = count($selectedLandmarks) === 1
                ? 'dekat dengan ' . $selectedLandmarks[0]
                : 'dekat dengan ' . $selectedLandmarks[0] . ' dan ' . $selectedLandmarks[1];

            $descriptionTemplates = [
                'Kos nyaman dan strategis di area ' . $areaName . ', ' . $landmarkText . '. Dilengkapi dengan berbagai fasilitas pendukung untuk kenyamanan penghuni.',
                'Lokasi sangat mantap di ' . $areaName . ', ' . $landmarkText . '. Tempat kost yang cocok untuk mahasiswa dan pekerja.',
                'Kos elok di kawasan ' . $areaName . ', berjarak dekat ke ' . $selectedLandmarks[0] . '. Akses jalan mudah dan aman.',
                'Tempat tinggal nyaman di ' . $areaName . ', ' . $landmarkText . '. Fasilitas lengkap, lingkungan bersih dan kondusif.',
            ];

            $kos = BoardingHouse::create([
                'admin_id' => $admin->id,
                'name' => $name,
                'description' => $descriptionTemplates[array_rand($descriptionTemplates)],
                'address' => 'Jl. ' . $faker->streetName() . ' No. ' . rand(1, 100),
                'public_contact_name' => $admin->name,
                'public_contact_whatsapp_number' => '08' . mt_rand(1000000000, 9999999999),
                'city' => $cityPalu->name,
                'district' => $district->name,
                'subdistrict' => $village->name,
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

            // Create 8 Photos (1 per category)
            $categories = ['bangunan_depan', 'dalam_kamar', 'kamar_mandi', 'ruang_tamu', 'dapur', 'area_parkir', 'fasilitas_umum', 'lainnya'];
            $p = 1;
            foreach ($categories as $cat) {
                // Gunakan foto lokal sesuai kategori jika ada
                if (!empty($availablePhotos[$cat])) {
                    $photoUrl = $availablePhotos[$cat][array_rand($availablePhotos[$cat])];
                } else {
                    // Jika kategori tersebut kosong (misal 'lainnya'), ambil acak dari SEMUA foto dummy lokal yang ada
                    $allDummyPhotos = array_merge(...array_values($availablePhotos));
                    if (!empty($allDummyPhotos)) {
                        $photoUrl = $allDummyPhotos[array_rand($allDummyPhotos)];
                    } else {
                        // Fallback terakhir ke internet jika folder dummy_data/photos benar-benar kosong total
                        $lockId = ($i * 10) + $p;
                        $photoUrl = 'https://loremflickr.com/800/600/' . $photoKeywords[$cat] . '?lock=' . $lockId;
                    }
                }

                BoardingHousePhoto::create([
                    'boarding_house_id' => $kos->id,
                    'file_path' => $photoUrl,
                    'category' => $cat,
                    'is_primary' => ($cat === 'bangunan_depan'),
                    'sort_order' => $p,
                ]);
                $p++;
            }

            // Create Legal Documents
            BoardingHouseLegalDocument::create([
                'boarding_house_id' => $kos->id,
                'document_type' => 'identitas_pemilik_pengelola',
                'document_name' => 'KTP Pemilik',
                'document_number' => '72710' . mt_rand(10000000000, 99999999999),
                'file_path' => $ktpPath,
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
                'file_path' => $shmPath,
                'status' => 'valid',
                'review_note' => 'Dokumen asli dan sesuai lokasi',
                'reviewed_by' => $superAdmin->id,
                'reviewed_at' => Carbon::now(),
            ]);
        }

        $this->command->info('Berhasil menambahkan 50 data Kos Dummy di Kota Palu (Data Daerah Real dari Laravolt, Spesifikasi Kamar Seragam, Foto Realistis)!');
    }
}
