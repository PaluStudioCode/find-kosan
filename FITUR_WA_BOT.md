# Fitur dan Kemampuan WhatsApp Bot (CariKosanMu AI)

Dokumen ini berisi daftar fitur dan jenis pertanyaan yang dapat direspon oleh AI Assistant WhatsApp pada platform CariKosanMu.

## Fitur Utama
- **Pencarian Kos Pintar:** Menggunakan AI untuk memahami konteks pencarian pengguna berdasarkan lokasi, harga, atau fasilitas.
- **Manajemen Peran (Role-Based):** Memberikan jawaban yang disesuaikan dengan status pengguna (Guest, Penyewa, atau Pemilik Kos).
- **Penanganan Privasi (LID):** Mampu menangani pengguna yang menyembunyikan nomor WhatsApp-nya dengan fitur autentikasi OTP (`/login` dan `/otp`).
- **Pembatasan (Rate Limit):** Melindungi server dari potensi spam (maksimal 20 pesan per menit per pengguna).
- **Aman dari Manipulasi:** Dirancang hanya sebagai jembatan informasi (Read-Only) yang tidak bisa disuruh memanipulasi data pembayaran atau status sewa, sehingga aman dari *prompt injection*.

---

## Daftar Pertanyaan Berdasarkan Peran (Role)

### 1. Guest (Tamu / Publik)
Pengguna publik yang belum terdaftar atau belum menautkan nomor teleponnya.
**Kemampuan:** Pencarian informasi publik platform dan kos-kosan.

**Contoh Pertanyaan yang Bisa Dijawab:**
- "Cari kos di dekat Universitas Tadulako."
- "Ada rekomendasi kos di Palu Selatan yang harganya di bawah 1 juta?"
- "Tolong carikan kos yang ada fasilitas WiFi dan AC."
- "Carikan kos khusus putri di daerah Silae."
- "Bisa minta detail Kos Najib Palu Utara?"
- "Berapa harga kamar di Kos Eli Ulujadi?"
- "Apakah Kos Rahayu Mantikulore masih ada kamar kosong?"
- "Apa saja fasilitas di Kos Luluh Mantikulore?"
- "Berapa persen pajak (PPN) yang dikenakan di platform ini?"
- "Bagaimana cara menghubungi admin aplikasi CariKosanMu?"
- "Apa syarat dan ketentuan menyewa kos di sini?"

### 2. Penyewa (User)
Pengguna yang sudah terdaftar dan memiliki riwayat atau status sewa aktif di platform.
**Kemampuan:** Semua kemampuan Guest + Informasi sewa dan tagihan pribadi.

**Contoh Pertanyaan yang Bisa Dijawab:**
- *(Semua pertanyaan Guest di atas)*
- "Tolong cek status sewa kos saya."
- "Kapan masa sewa saya di Kos Najib habis?"
- "Saya ngekos di kamar nomor berapa ya, dan berapa harga sewa saya saat ini?"
- "Bisa minta kontak WhatsApp bapak kos saya?"
- "Apakah saya punya tagihan kos bulan ini yang belum dibayar?"
- "Berapa total tagihan sewa saya yang jatuh tempo?"
- "Tolong rincikan tagihan bulan ini beserta pajaknya."

### 3. Pemilik Kos (Admin/Owner)
Pengguna yang memiliki dan mengelola properti kos di platform.
**Kemampuan:** Semua kemampuan Guest + Ringkasan statistik properti & keuangan (Dompet).

**Contoh Pertanyaan yang Bisa Dijawab:**
- *(Semua pertanyaan Guest di atas)*
- "Tolong berikan ringkasan semua kos yang saya kelola."
- "Berapa jumlah penyewa aktif di kos saya saat ini?"
- "Berapa kamar kosong yang masih tersedia di Kos Mawar?"
- "Ada berapa kamar yang terisi saat ini di semua properti saya?"
- "Berapa saldo dompet (wallet) saya saat ini?"
- "Berapa jumlah uang yang sedang dalam proses penarikan (pending withdrawal)?"
- "Apakah ada penyewa yang belum bayar tagihannya bulan ini?"

---

## Daftar Command (Perintah Khusus)
Selain bahasa natural sehari-hari, bot juga mengenali perintah sistem (command) berikut:

- `/login [Nomor WA]` : Meminta kode OTP rahasia untuk menautkan obrolan. Sangat berguna jika nomor pengguna disembunyikan oleh WhatsApp (Linked Device / LID) karena privasi.
- `/otp [Kode]` : Memverifikasi kode OTP untuk menghubungkan nomor WA asli dengan sesi obrolan saat ini.
- `/reset` atau `/clear` : Menghapus paksa memori/riwayat percakapan pengguna dengan AI untuk memulai konteks obrolan yang baru.
