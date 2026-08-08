# Panduan Lengkap Kemampuan FindKos AI (WhatsApp Bot)

FindKos AI adalah asisten virtual WhatsApp yang terintegrasi langsung dengan database CariKosanMu (FindKosan). AI ini dilengkapi dengan fitur _Role-Based Access Control_ (RBAC) yang cerdas dan aman melalui OTP.

Berikut adalah rincian lengkap mengenai apa saja yang bisa ditanyakan dan dijawab oleh AI berdasarkan masing-masing peran (role) pengguna:

## 1. Fitur Keamanan & Identifikasi (Semua Pengguna)
- **Deteksi Nomor Otomatis**: Bot secara otomatis mengenali nomor pengirim jika nomor tersebut tidak disembunyikan oleh WhatsApp.
- **Sistem Login OTP**: Jika nomor pengguna disembunyikan (menggunakan fitur Linked Device / LID Meta), bot akan menolak memberikan informasi sensitif. Pengguna harus mengetik `/login [nomor_wa]` dan memverifikasi diri menggunakan kode rahasia (`/otp [kode]`) yang dikirim ke nomor asli pengguna.
- **Reset Ingatan**: Pengguna dapat mengetik `/reset` atau `/clear` kapan saja untuk menghapus konteks obrolan lama dan memulai percakapan baru yang segar.

## 2. Kemampuan GUEST (Tamu / Belum Login / Belum Terdaftar)
Pengguna yang nomornya tidak terdaftar di sistem, atau belum memverifikasi identitasnya, hanya dapat mengakses informasi publik.

**Yang Bisa Ditanyakan:**
- **Pencarian Kos:** "Cari kos di Palu Selatan yang ada WiFi dan parkir, budget 700 ribu."
- **Ketersediaan Kamar:** "Apakah Kos Mawar masih ada kamar kosong?"
- **Detail Kos:** "Tolong info lengkap tentang Kos Harmoni dong." (Bot akan memberikan alamat, deskripsi, rentang harga, fasilitas, aturan larangan, dan ulasan terbaru).
- **Info Platform:** "Apa itu FindKosan? Gimana cara pesannya?"
- **Tautan Langsung:** Setiap kali bot merekomendasikan kos, bot akan selalu memberikan _link_ URL yang bisa langsung diklik untuk melihat detail atau memesan di website.

*Batasan:* Guest tidak dapat menanyakan tagihan, riwayat sewa, atau saldo.

## 3. Kemampuan USER (Penyewa)
Pengguna yang nomornya terdaftar sebagai akun Penyewa memiliki akses penuh ke profil penyewaan mereka.

**Semua kemampuan GUEST ditambah:**
- **Cek Tagihan (Invoice):** "Apakah saya punya tagihan yang belum dibayar bulan ini?" atau "Berapa total tagihan saya?"
- **Jatuh Tempo:** "Kapan tagihan saya jatuh tempo?"
- **Info Kos yang Ditempati:** "Saya sekarang nyewa kamar nomor berapa ya?" atau "Kontrak kos saya sampai kapan?"
- **Detail Harga Sewa:** "Tolong rincikan tagihanku (uang sewa + PPN)."

*Batasan:* Bot tidak dapat menerima pembayaran langsung. Bot akan mengarahkan penyewa untuk melakukan pembayaran di website (via Duitku/QRIS).

## 4. Kemampuan ADMIN (Pemilik Kos)
Pengguna yang terdaftar sebagai Pemilik Kos dapat memantau bisnis mereka langsung dari WhatsApp.

**Semua kemampuan GUEST ditambah:**
- **Ringkasan Properti:** "Tolong rekap data semua kos saya."
- **Statistik Okupansi:** "Berapa banyak kamar yang kosong dan terisi di kos saya?"
- **Daftar Penyewa Aktif:** "Berapa jumlah penyewa aktif saya saat ini?"
- **Cek Tunggakan:** "Apakah ada penyewa yang nunggak bayar?"
- **Cek Saldo Pendapatan:** "Berapa saldo dompet penghasilan saya saat ini?"

*Batasan:* Bot tidak dapat digunakan untuk menambah kamar baru, mengedit data kos, atau menarik dana (withdrawal). Aksi-aksi ini harus dilakukan melalui Dashboard Admin di website.

## 5. Batasan Sistem (Perilaku Bot)
- **Anti-Spam (Rate Limit):** Dibatasi maksimal 20 pesan per menit per nomor.
- **Hanya Teks:** Bot tidak akan memproses Gambar, Voice Note, Sticker, atau Dokumen. Jika dikirim, bot akan membalas dengan sopan: *"Maaf, saat ini saya hanya bisa memproses pesan teks..."*
- **Satu Arah untuk Aksi:** Bot bersifat *Read-Only* (hanya mengambil informasi). Bot tidak bisa diperintah untuk mengubah nama, membatalkan pesanan, atau menyetujui transaksi.
