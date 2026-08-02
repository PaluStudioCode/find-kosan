<?php

namespace Database\Seeders;

use App\Models\BoardingHouseReview;
use App\Models\Tenancy;
use Illuminate\Database\Seeder;

class ReviewDummySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Memulai pembuatan dummy rating dan komentar kos...');

        $tenancies = Tenancy::all();

        if ($tenancies->isEmpty()) {
            $this->command->error('Tidak ada data penyewa (Tenancy). Jalankan TenancyDummySeeder terlebih dahulu.');
            return;
        }

        $comments5Stars = [
            "Kosnya bersih dan nyaman banget. Fasilitas lengkap sesuai deskripsi, penjaganya juga ramah. Betah ngekos di sini!",
            "Sangat direkomendasikan! Harga sebanding dengan fasilitas yang didapat. Suasananya tenang, cocok buat nugas atau WFH.",
            "Kamar mandinya bersih, sirkulasi udara bagus, dan parkiran aman. Ibu kosnya juga sangat pengertian. Top deh!",
            "Pengalaman yang luar biasa. Kosnya terawat dengan sangat baik, air lancar 24 jam. Pokoknya mantap!",
            "Nyaman banget, serasa di rumah sendiri. Dekat dengan tempat makan dan minimarket. Bintang 5 pokoknya!",
            "Fasilitas super lengkap, kebersihan terjaga, dan keamanan terjamin. Sangat worth it!"
        ];

        $comments4Stars = [
            "Bagus, sirkulasi udaranya oke dan area parkirnya lumayan luas. Cuma sinyal wifi kadang agak turun di malam hari.",
            "Secara keseluruhan sangat nyaman. Lingkungannya enak dan tenang, cuma akses jalannya agak sempit.",
            "Kamar dan fasilitas sesuai foto, bersih juga. Sedikit kurang kedap suara kalau ada tetangga yang bising, tapi sisanya aman.",
            "Fasilitas lumayan memadai dan penjaga kos sigap. Recommended buat yang cari kos budget menengah.",
            "Harga cukup bersahabat. Semuanya bagus, hanya saja jemurannya agak terbatas kalau lagi musim hujan."
        ];

        $comments3Stars = [
            "Standar aja sih, sesuai dengan harga. Airnya lancar, cuma kamarnya agak panas kalau siang.",
            "Lumayan buat tempat istirahat sementara. Kebersihan oke, tapi fasilitas dapur bersamanya butuh perbaikan.",
            "Kosnya cukup nyaman, tapi penjaganya jarang kelihatan kalau ada keluhan. Yah, ada rupa ada harga.",
            "Biasa aja, kasurnya agak keras dan wifi sering putus. Tapi kebersihannya lumayan dijaga."
        ];

        $reviewCount = 0;

        foreach ($tenancies as $tenancy) {
            // Berikan probabilitas 85% bagi tenant untuk memberikan ulasan
            if (rand(1, 100) <= 85) {
                
                // Acak rating (50% bintang 5, 40% bintang 4, 10% bintang 3)
                $rand = rand(1, 100);
                if ($rand <= 50) {
                    $rating = 5;
                    $comment = $comments5Stars[array_rand($comments5Stars)];
                } elseif ($rand <= 90) {
                    $rating = 4;
                    $comment = $comments4Stars[array_rand($comments4Stars)];
                } else {
                    $rating = 3;
                    $comment = $comments3Stars[array_rand($comments3Stars)];
                }

                // Waktu ulasan: 2 s/d 25 hari setelah tanggal masuk kos (mulai sewa)
                $reviewDate = clone $tenancy->start_date;
                $reviewDate->addDays(rand(2, 25));
                
                // Jika waktu ulasan melebihi waktu saat ini, setel ulang ke sekarang agar tidak ada komentar dari masa depan
                if ($reviewDate->isFuture()) {
                    $reviewDate = now();
                }

                BoardingHouseReview::create([
                    'boarding_house_id' => $tenancy->boarding_house_id,
                    'user_id' => $tenancy->user_id,
                    'rating' => $rating,
                    'comment' => $comment,
                    'created_at' => $reviewDate,
                    'updated_at' => $reviewDate,
                ]);

                $reviewCount++;
            }
        }

        $this->command->info("Seeding ulasan selesai. Total {$reviewCount} ulasan berhasil ditambahkan ke 50 kos.");
    }
}
