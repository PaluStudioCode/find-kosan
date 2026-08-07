<?php

namespace App\Services;

use App\Helpers\WhatsappNumber;
use App\Models\BoardingHouse;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Tenancy;
use App\Models\User;
use App\Models\WaBotConversation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class WhatsappBotContextService
 *
 * Service untuk:
 *  1. Identifikasi sender berdasarkan nomor WA (lookup users.whatsapp_number)
 *  2. Fetch context data per role (penyewa/pemilik/public) — untuk function calling
 *  3. Definisi tools yang tersedia untuk LLM (function calling)
 *  4. Eksekusi tool call dari LLM
 *  5. Build system prompt
 *
 * Arsitektur: Function Calling (bukan inject context ke system prompt).
 * LLM akan memanggil tool saat butuh data spesifik.
 */
class WhatsappBotContextService
{
    /**
     * Identifikasi sender berdasarkan JID WhatsApp.
     *
     * - Jika JID berakhiran @s.whatsapp.net → extract nomor telepon → lookup users.whatsapp_number
     * - Jika JID berakhiran @lid → tidak bisa dapat nomor telepon (privasi WA) → role = public
     *
     * Preferensi role (untuk @s.whatsapp.net): user (penyewa) > admin (pemilik) > super_admin.
     *
     * @param string $fromJid JID asli lengkap (mis. "628xxx@s.whatsapp.net" atau "173xxx@lid")
     * @return array{user: ?User, role: string, phone: ?string}
     *   - user: User model atau null (jika public/@lid)
     *   - role: 'user' | 'admin' | 'super_admin' | 'public'
     *   - phone: nomor telepon normalized (atau null jika @lid)
     */
    public function identifySender(string $fromJid): array
    {
        // Jika @lid → tidak bisa identifikasi user, anggap public
        if (str_ends_with($fromJid, '@lid')) {
            return ['user' => null, 'role' => 'public', 'phone' => null];
        }

        // Jika @s.whatsapp.net → extract nomor telepon
        if (! str_ends_with($fromJid, '@s.whatsapp.net')) {
            return ['user' => null, 'role' => 'public', 'phone' => null];
        }

        $phone = WhatsappNumber::normalize(str_replace('@s.whatsapp.net', '', $fromJid));

        // Cari kandidat nomor dalam beberapa format yang mungkin tersimpan di DB:
        // 1. Format normalized 62xxx (standar baru)
        // 2. Format 08xxx (standar lama, konversi dari 62xxx)
        $candidates = [$phone];
        if (str_starts_with($phone, '62')) {
            $candidates[] = '0' . substr($phone, 2);
        }

        $user = User::whereIn('whatsapp_number', $candidates)
            ->orderByRaw("FIELD(role, 'user', 'admin', 'super_admin')")
            ->first();

        if (! $user) {
            return ['user' => null, 'role' => 'public', 'phone' => $phone];
        }

        return ['user' => $user, 'role' => $user->role, 'phone' => $phone];
    }

    /**
     * Upsert WaBotConversation berdasarkan JID asli.
     * Simpan from_jid untuk balasan ke JID yang benar.
     */
    public function upsertConversation(string $fromJid, ?User $user, string $role, ?string $phone): WaBotConversation
    {
        // phone_number = nomor telepon (jika @s.whatsapp.net) atau ID numeric (jika @lid)
        // dari pada null, gunakan ID numeric dari JID agar unique constraint terpenuhi
        $phoneForColumn = $phone ?: str_replace(['@s.whatsapp.net', '@lid'], '', $fromJid);

        return WaBotConversation::updateOrCreate(
            ['from_jid' => $fromJid],
            [
                'phone_number' => $phoneForColumn,
                'user_id' => $user?->id,
                'identified_role' => $role,
                'last_message_at' => now(),
            ]
        );
    }

    /**
     * Definisi tools yang tersedia untuk LLM (OpenAI tools format).
     * Tools yang tersedia dibatasi per role agar LLM tidak memanggil tool di luar konteks.
     *
     * @param string $role 'user' | 'admin' | 'public'
     * @return array
     */
    public function getToolsForRole(string $role): array
    {
        $tools = [];

        // ===== Tools untuk PENYEWA (user) =====
        if ($role === 'user') {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_invoices',
                    'description' => 'Ambil daftar tagihan sewa milik penyewa. Bisa filter berdasarkan status. Return list invoice dengan id, amount, due_date, status, period.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type' => 'string',
                                'enum' => ['belum_dibayar', 'jatuh_tempo', 'lunas', 'semua'],
                                'description' => 'Status invoice. Default: semua tagihan aktif (belum_dibayar & jatuh_tempo).',
                            ],
                        ],
                    ],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_tenancy',
                    'description' => 'Ambil info sewa aktif penyewa: kos, kamar, nama pemilik, nomor WhatsApp pemilik (berguna untuk meneruskan keluhan/laporan), tanggal mulai/selesai, jumlah penghuni.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_payment_history',
                    'description' => 'Ambil riwayat pembayaran penyewa (invoice yang sudah lunas).',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ];
        }

        // ===== Tools untuk PEMILIK KOS (admin) =====
        if ($role === 'admin') {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_kos_list',
                    'description' => 'Ambil daftar kos milik pemilik (maksimal 5 teratas) dengan status verifikasi, jumlah kamar, kamar terisi/kosong.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_wallet',
                    'description' => 'Ambil info saldo wallet pemilik: available_balance, pending_withdrawal_balance, dan jumlah withdrawal request pending.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_tenants',
                    'description' => 'Ambil daftar penyewa aktif di semua kos milik pemilik: nama, kamar, kos, tanggal mulai sewa.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_reviews',
                    'description' => 'Ambil ringkasan ulasan kos milik pemilik: rating rata-rata, jumlah review, review terbaru.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_platform_fees',
                    'description' => 'Ambil info PPN (pajak penyewa) dan PPh (pajak pemilik) dari pengaturan platform.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ];
        }

        // ===== Tools untuk PUBLIC (belum terdaftar) & PENYEWA =====
        // Tool pencarian kos by keyword/lokasi tersedia untuk public & user
        if (in_array($role, ['public', 'user'], true)) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'search_kos_by_keyword',
                    'description' => 'Cari kos berdasarkan lokasi, nama daerah, atau landmark terdekat (mis. kampus, rumah sakit, stasiun). '
                        . 'PENTING: (1) SELALU koreksi typo/salah ketik dari pengguna sebelum mengirim keyword (contoh: "dpok" → "Depok", "sby" → "Surabaya", "jkrta" → "Jakarta"). '
                        . '(2) Jika pencarian dengan nama landmark tidak menemukan hasil, coba cari ULANG menggunakan nama kota/kecamatan dari landmark tersebut (contoh: "Kampus UI" tidak ditemukan → coba "Depok"). '
                        . '(3) Gunakan nama resmi/lengkap daerah, bukan singkatan.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'keyword' => [
                                'type' => 'string',
                                'description' => 'Kata kunci pencarian yang sudah dikoreksi (tanpa typo). Bisa berupa nama wilayah (kota/kecamatan/kelurahan), nama landmark, atau kata kunci lokasi.',
                            ],
                        ],
                        'required' => ['keyword'],
                    ],
                ],
            ];
        }

        // ===== Tools untuk PUBLIC & PENYEWA =====
        // Penyewa mencakup semua kemampuan Tamu (sesuai panduan bot)
        if (in_array($role, ['public', 'user'], true)) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_featured_kos',
                    'description' => 'Ambil daftar kos rekomendasi (5 kos dipublikasikan terbaru) dengan nama, kota, harga mulai, jumlah kamar tersedia, dan link detail.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_platform_info',
                    'description' => 'Ambil info platform CariKosanMu: total kos terdaftar, total kamar tersedia, kontak, cara kerja platform, dan cara mendaftar.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ];
        }

        return $tools;
    }

    /**
     * Eksekusi tool call dari LLM.
     *
     * @param string $toolName Nama function yang dipanggil LLM
     * @param array $arguments Arguments dari LLM (decoded JSON)
     * @param ?User $sender User pengirim (null jika public)
     * @return string Hasil eksekusi tool (JSON string untuk dikirim balik ke LLM)
     */
    public function executeTool(string $toolName, array $arguments, ?User $sender): string
    {
        try {
            $result = match ($toolName) {
                'get_my_invoices' => $this->toolGetMyInvoices($sender, $arguments['status'] ?? 'semua'),
                'get_my_tenancy' => $this->toolGetMyTenancy($sender),
                'get_payment_history' => $this->toolGetPaymentHistory($sender),
                'get_my_kos_list' => $this->toolGetMyKosList($sender),
                'get_my_wallet' => $this->toolGetMyWallet($sender),
                'get_my_tenants' => $this->toolGetMyTenants($sender),
                'get_my_reviews' => $this->toolGetMyReviews($sender),
                'get_platform_fees' => $this->toolGetPlatformFees(),
                'get_featured_kos' => $this->toolGetFeaturedKos(),
                'get_platform_info' => $this->toolGetPlatformInfo(),
                'search_kos_by_keyword' => $this->toolSearchKosByKeyword($arguments['keyword'] ?? ''),
                default => ['error' => "Unknown tool: {$toolName}"],
            };

            return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            Log::error('[WA Bot] Tool execution failed', [
                'tool' => $toolName,
                'error' => $e->getMessage(),
            ]);
            return json_encode(['error' => 'Tool execution failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Build system prompt untuk LLM.
     *
     * @param ?User $sender User pengirim (null jika public)
     * @param string $role 'user' | 'admin' | 'public'
     * @param ?string $contextSummary Ringkasan percakapan sebelumnya (dari kompresi)
     */
    public function buildSystemPrompt(?User $sender, string $role, ?string $contextSummary = null): string
    {
        $senderName = $sender?->name;
        $identifiedText = match ($role) {
            'user' => "Penyewa terdaftar" . ($senderName ? " bernama *{$senderName}*" : ''),
            'admin' => "Pemilik Kos terdaftar" . ($senderName ? " bernama *{$senderName}*" : ''),
            'super_admin' => "Super Admin platform",
            default => "Tamu (belum terdaftar di platform)",
        };

        $appUrl = config('app.url');
        $contactEmail = Setting::getSetting('contact_email', 'support@carikosanmu.com');
        $appName = Setting::getSetting('app_name', 'CariKosanMu');
        $contactPhone = Setting::getSetting('contact_phone', '-');

        $summaryBlock = '';
        if ($contextSummary) {
            $summaryBlock = <<<SUMMARY


[RINGKASAN PERCAKAPAN SEBELUMNYA]
{$contextSummary}
[AKHIR RINGKASAN]
SUMMARY;
        }

        // Role-specific instructions
        $roleInstructions = $this->getRoleInstructions($role, $appUrl, $contactEmail);

        return <<<PROMPT
Anda adalah *Asisten {$appName}*, Customer Service profesional untuk platform kos-kosan {$appName}.

Pengirim saat ini: {$identifiedText}.{$summaryBlock}

══════════════════════════════════
1. PROTOKOL ANTI-HALUSINASI (PRIORITAS TERTINGGI)
══════════════════════════════════
- Anda DILARANG KERAS mengarang, menebak, atau mengasumsikan data APA PUN (harga, tagihan, status kamar, nama pemilik, fasilitas, jumlah kamar kosong, dsb).
- Untuk SETIAP pertanyaan yang membutuhkan data, Anda WAJIB memanggil tools yang tersedia. Tidak ada pengecualian.
- Jika hasil tool kosong atau mengandung "error" → jawab jujur: "Mohon maaf Kak, data tersebut belum tersedia saat ini. Silakan cek melalui website kami di {$appUrl}" — JANGAN mengarang data pengganti.
- Jika Anda TIDAK memiliki tool yang relevan untuk menjawab → katakan jujur bahwa Anda belum bisa membantu hal tersebut dan arahkan ke website atau email {$contactEmail}.
- Jika tool mengembalikan field "message_to_ai" → ikuti instruksi tersebut sebagai panduan jawaban Anda.

══════════════════════════════════
2. BATASAN DOMAIN (KETAT)
══════════════════════════════════
Anda HANYA boleh menjawab pertanyaan seputar:
✅ Platform {$appName} (cara kerja, pendaftaran, fitur)
✅ Pencarian kos & informasi kamar
✅ Tagihan, pembayaran, dan riwayat transaksi
✅ Informasi sewa (tenancy), pemilik kos, penyewa
✅ Wallet, penarikan saldo, biaya platform, pajak (PPN/PPh)
✅ Ulasan dan rating kos
✅ Sapaan umum dan basa-basi singkat (halo, terima kasih, selamat pagi)

Jika pengguna bertanya di LUAR topik di atas (politik, resep, berita, cuaca, coding, curhat, dll):
→ Tolak dengan sopan: "Mohon maaf Kak, saya khusus membantu seputar layanan {$appName} ya. Ada yang bisa saya bantu soal kos-kosan? 😊"

══════════════════════════════════
3. ATURAN BISNIS PLATFORM
══════════════════════════════════
- Harga kos bersifat FINAL, bot TIDAK melayani negosiasi harga.
- Refund/pengembalian dana TIDAK ditangani via bot → arahkan ke email {$contactEmail}.
- Pembayaran WAJIB dilakukan melalui website {$appUrl} (payment gateway Duitku). Bot tidak bisa memproses pembayaran.
- Bot ini TEXT-ONLY. Tidak bisa menerima/mengirim gambar, dokumen, PDF, lokasi, atau file. Jika pengguna mengirim media, abaikan dan jelaskan bahwa bot hanya memproses teks.
- Pendaftaran akun baru di: {$appUrl}/register
- Jika percakapan terasa bingung/kacau, pengguna bisa ketik /reset untuk mereset riwayat chat.

══════════════════════════════════
4. FAQ PLATFORM {$appName}
══════════════════════════════════
PENTING: Gunakan FAQ di bawah ini HANYA jika pengguna secara EKSPLISIT bertanya tentang platform (misalnya "apa itu {$appName}?", "bagaimana cara daftar?", "bagaimana cara bayar?"). JANGAN menyisipkan informasi FAQ ke dalam jawaban yang tidak berhubungan (misalnya saat user hanya minta cari kos). JANGAN mengarang jawaban di luar fakta ini:

Q: Apa itu {$appName}?
A: {$appName} adalah platform digital yang memudahkan pencarian dan pengelolaan kos-kosan secara online. Penyewa bisa mencari kos, melihat detail & foto, lalu melakukan pembayaran online. Pemilik kos bisa mendaftarkan propertinya, mengelola penyewa, dan menerima pembayaran.

Q: Bagaimana cara kerja platform ini?
A: Alurnya sederhana: (1) Penyewa mencari kos di website {$appUrl} → (2) Pilih kos & kamar yang sesuai → (3) Booking dan bayar online melalui payment gateway → (4) Pemilik kos mendapat notifikasi otomatis → (5) Penyewa mulai menempati kamar. Semua proses transparan dan tercatat di sistem.

Q: Bagaimana cara mendaftar?
A: Kunjungi {$appUrl}/register, isi data diri (nama, email, password), verifikasi email, lalu akun siap digunakan. Juga tersedia opsi login menggunakan akun Google.

Q: Bagaimana cara mendaftarkan kos saya sebagai pemilik?
A: Daftar sebagai pemilik di website, kemudian tambahkan properti kos lengkap dengan foto, fasilitas, harga kamar, dan dokumen legal. Setelah diverifikasi oleh tim {$appName}, kos Anda akan dipublikasikan dan bisa ditemukan oleh penyewa.

Q: Bagaimana cara bayar sewa?
A: Masuk ke akun penyewa di website {$appUrl}, buka halaman tagihan/invoice, lalu klik bayar. Pembayaran diproses melalui payment gateway Duitku dengan berbagai metode (transfer bank, e-wallet, dll).

Q: Ada berapa kos yang tersedia?
A: Gunakan tool get_platform_info atau get_featured_kos untuk mendapatkan data real-time. JANGAN mengarang angka.

══════════════════════════════════
5. GAYA KOMUNIKASI CUSTOMER SERVICE
══════════════════════════════════
- Bahasa Indonesia yang ramah dan luwes. Sapaan: "Kak" atau nama pengguna jika diketahui.
- Singkat dan to-the-point. Maksimal 2-3 paragraf pendek. Langsung jawab inti pertanyaan.
- JANGAN mengulang sapaan ("Halo!", "Hai!") di setiap pesan setelah percakapan sudah berjalan.
- JANGAN mengulang/menyalin ulang pertanyaan pengguna.
- DILARANG menyebut: "Sebagai AI...", "Sebagai bot...", "Berdasarkan sistem...", "Menurut database...", atau istilah teknis (JSON, API, tool, function call).
- Format: *tebal* untuk info penting, Rp1.000.000 untuk harga.
- Emoji secukupnya (1-2 per pesan, tidak berlebihan).
- Jika menampilkan daftar data (tagihan, kos, penyewa), gunakan format daftar yang rapi dengan nomor urut.
- Akhiri jawaban informatif dengan tawaran bantuan singkat: "Ada lagi yang bisa saya bantu, Kak?" (variasikan kalimatnya, jangan monoton).

══════════════════════════════════
5b. ATURAN ANTI-PENGULANGAN (SANGAT PENTING)
══════════════════════════════════
- DILARANG KERAS menjelaskan apa itu {$appName} atau deskripsi platform KECUALI pengguna secara EKSPLISIT bertanya "apa itu {$appName}?" atau pertanyaan serupa tentang platform.
- Jika pengguna minta CARI KOS → langsung panggil tool search_kos_by_keyword dan tampilkan hasilnya. JANGAN menambahkan penjelasan tentang platform.
- Jika pengguna minta REKOMENDASI KOS → langsung panggil tool get_featured_kos dan tampilkan hasilnya. JANGAN menambahkan penjelasan tentang platform.
- JANGAN PERNAH mengulang informasi yang sudah disampaikan di pesan-pesan sebelumnya dalam percakapan yang sama.
- Setiap jawaban harus LANGSUNG menjawab pertanyaan pengguna, tanpa pembukaan atau penjelasan yang tidak diminta.
- Jika tool mengembalikan hasil kosong, cukup katakan tidak ditemukan dan tawarkan alternatif pencarian. JANGAN mengisi kekosongan dengan deskripsi platform.

══════════════════════════════════
6. PENANGANAN KELUHAN (ESKALASI)
══════════════════════════════════
Jika penyewa mengeluh (fasilitas rusak, masalah keamanan, dsb):
1. Tunjukkan empati terlebih dahulu.
2. Panggil tool 'get_my_tenancy' untuk mendapatkan kontak pemilik kos.
3. Berikan nama dan nomor WhatsApp pemilik agar penyewa bisa menghubungi langsung.
4. Jika kontak tidak ditemukan → arahkan ke fitur "Laporan" di {$appUrl} atau email {$contactEmail}.

══════════════════════════════════
7. INSTRUKSI KHUSUS ROLE
══════════════════════════════════
{$roleInstructions}

══════════════════════════════════
INFORMASI KONTAK PLATFORM
══════════════════════════════════
- Website: {$appUrl}
- Email: {$contactEmail}
- Telepon: {$contactPhone}
PROMPT;
    }

    /**
     * Instruksi khusus per role untuk system prompt.
     */
    protected function getRoleInstructions(string $role, string $appUrl, string $contactEmail): string
    {
        return match ($role) {
            'user' => <<<ROLE
Pengirim adalah PENYEWA TERDAFTAR. Anda bisa membantu:
- Cek tagihan/invoice (gunakan tool get_my_invoices)
- Info sewa aktif, nama pemilik, kontak pemilik (gunakan tool get_my_tenancy)
- Riwayat pembayaran (gunakan tool get_payment_history)
- Cari kos lain (gunakan tool search_kos_by_keyword)
- Meneruskan keluhan ke pemilik kos (ambil kontak dari get_my_tenancy)
- Untuk membayar tagihan, arahkan SELALU ke {$appUrl}
ROLE,
            'admin' => <<<ROLE
Pengirim adalah PEMILIK KOS TERDAFTAR. Anda bisa membantu:
- Status kos & verifikasi (gunakan tool get_my_kos_list)
- Info kamar kosong/terisi (gunakan tool get_my_kos_list)
- Saldo wallet & info penarikan (gunakan tool get_my_wallet)
- Daftar penyewa aktif (gunakan tool get_my_tenants)
- Ulasan & rating kos (gunakan tool get_my_reviews)
- Penjelasan biaya platform & pajak PPN/PPh (gunakan tool get_platform_fees)
- Untuk mengelola kos lebih lanjut, arahkan ke dashboard di {$appUrl}/admin
ROLE,
            'super_admin' => <<<ROLE
Pengirim adalah SUPER ADMIN. Perlakukan dengan hormat. Anda hanya bisa membantu dengan informasi umum platform. Untuk pengelolaan sistem, arahkan ke dashboard di {$appUrl}/superadmin.
ROLE,
            default => <<<ROLE
Pengirim adalah TAMU (belum terdaftar). Anda bisa membantu:
- Menjawab pertanyaan tentang platform (gunakan FAQ di atas, JANGAN mengarang)
- Info kos terbaru/rekomendasi (gunakan tool get_featured_kos)
- Cari kos berdasarkan lokasi (gunakan tool search_kos_by_keyword)
- Info statistik platform (gunakan tool get_platform_info)
- Panduan cara mendaftar → arahkan ke {$appUrl}/register
- JANGAN mengarang data apa pun. Jika tidak ada tool yang bisa menjawab, arahkan ke website.
ROLE,
        };
    }

    // ===================== TOOL IMPLEMENTATIONS =====================

    /** Tool: get_my_invoices (penyewa) */
    protected function toolGetMyInvoices(?User $sender, string $status): array
    {
        if (! $sender) {
            return [
                'error' => 'User tidak teridentifikasi',
                'message_to_ai' => 'Nomor WhatsApp pengirim tidak cocok dengan data di sistem. Beritahu pengguna bahwa nomor mereka belum terdaftar dan arahkan untuk memperbarui nomor WA di halaman Profil website.',
            ];
        }

        $query = Invoice::where('user_id', $sender->id)
            ->with(['tenancy.room.boardingHouse'])
            ->orderByDesc('due_date');

        if ($status === 'semua' || empty($status)) {
            $query->whereIn('status', ['belum_dibayar', 'jatuh_tempo', 'lunas']);
        } elseif ($status !== 'lunas') {
            $query->whereIn('status', ['belum_dibayar', 'jatuh_tempo']);
        } else {
            $query->where('status', 'lunas');
        }

        $invoices = $query->limit(20)->get();

        if ($invoices->isEmpty()) {
            return [
                'count' => 0,
                'invoices' => [],
                'total_unpaid' => 0,
                'message_to_ai' => 'Tidak ada tagihan ditemukan untuk filter ini. Beritahu penyewa dengan ramah bahwa tidak ada tagihan yang cocok dengan permintaan mereka saat ini.',
            ];
        }

        return [
            'count' => $invoices->count(),
            'invoices' => $invoices->map(fn ($inv) => [
                'id' => $inv->id,
                'amount' => (float) $inv->amount,
                'due_date' => $inv->due_date?->format('Y-m-d'),
                'status' => $inv->status,
                'period' => ($inv->period_start?->format('Y-m-d')) . ' s/d ' . ($inv->period_end?->format('Y-m-d')),
                'kos_name' => $inv->tenancy?->boardingHouse?->name,
                'room_number' => $inv->tenancy?->room?->room_number,
            ]),
            'total_unpaid' => (float) $invoices->whereNotIn('status', ['lunas'])->sum('amount'),
        ];
    }

    /** Tool: get_my_tenancy (penyewa) */
    protected function toolGetMyTenancy(?User $sender): array
    {
        if (! $sender) {
            return [
                'error' => 'User tidak teridentifikasi',
                'message_to_ai' => 'Nomor WhatsApp pengirim tidak cocok dengan data di sistem. Arahkan pengguna untuk update nomor WA di halaman Profil website.',
            ];
        }

        $tenancies = Tenancy::where('user_id', $sender->id)
            ->whereIn('status', ['aktif', 'nonaktif'])
            ->with(['boardingHouse', 'room', 'admin'])
            ->limit(5)
            ->get();

        if ($tenancies->isEmpty()) {
            return [
                'count' => 0,
                'tenancies' => [],
                'message_to_ai' => 'Penyewa ini belum memiliki sewa aktif. Beritahu dengan ramah bahwa belum ada data sewa yang tercatat dan tawarkan bantuan untuk mencari kos.',
            ];
        }

        return [
            'count' => $tenancies->count(),
            'tenancies' => $tenancies->map(fn ($t) => [
                'kos_name' => $t->boardingHouse?->name,
                'kos_address' => $t->boardingHouse?->address,
                'room_number' => $t->room?->room_number,
                'owner_name' => $t->admin?->name,
                'owner_whatsapp' => $t->boardingHouse?->public_contact_whatsapp_number,
                'start_date' => $t->start_date?->format('Y-m-d'),
                'end_date' => $t->end_date?->format('Y-m-d'),
                'occupant_count' => $t->occupant_count,
                'status' => $t->status,
            ]),
        ];
    }

    /** Tool: get_payment_history (penyewa) */
    protected function toolGetPaymentHistory(?User $sender): array
    {
        if (! $sender) {
            return [
                'error' => 'User tidak teridentifikasi',
                'message_to_ai' => 'Nomor WhatsApp pengirim tidak cocok dengan data di sistem. Arahkan untuk update nomor WA di halaman Profil website.',
            ];
        }

        $invoices = Invoice::where('user_id', $sender->id)
            ->where('status', 'lunas')
            ->with(['payments'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        if ($invoices->isEmpty()) {
            return [
                'count' => 0,
                'paid_invoices' => [],
                'total_paid' => 0,
                'message_to_ai' => 'Belum ada riwayat pembayaran. Beritahu penyewa bahwa belum ada pembayaran yang tercatat di sistem.',
            ];
        }

        return [
            'count' => $invoices->count(),
            'paid_invoices' => $invoices->map(fn ($inv) => [
                'id' => $inv->id,
                'amount' => (float) $inv->amount,
                'paid_date' => $inv->updated_at?->format('Y-m-d'),
                'period' => ($inv->period_start?->format('Y-m-d')) . ' s/d ' . ($inv->period_end?->format('Y-m-d')),
                'kos_name' => $inv->tenancy?->boardingHouse?->name,
                'room_number' => $inv->tenancy?->room?->room_number,
            ]),
            'total_paid' => (float) $invoices->sum('amount'),
        ];
    }

    /** Tool: get_my_kos_list (pemilik) — maksimal 5 teratas */
    protected function toolGetMyKosList(?User $sender): array
    {
        if (! $sender) {
            return [
                'error' => 'User tidak teridentifikasi',
                'message_to_ai' => 'Nomor WhatsApp pengirim tidak cocok dengan data di sistem. Arahkan untuk update nomor WA di halaman Profil website.',
            ];
        }

        $kosList = BoardingHouse::where('admin_id', $sender->id)
            ->withCount(['rooms as total_rooms', 'rooms as occupied_rooms' => fn ($q) => $q->where('status', 'terisi')])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        if ($kosList->isEmpty()) {
            return [
                'count' => 0,
                'kos_list' => [],
                'message_to_ai' => 'Pemilik ini belum memiliki kos terdaftar. Beritahu dengan ramah dan arahkan untuk menambahkan kos melalui dashboard di website.',
            ];
        }

        return [
            'count' => $kosList->count(),
            'kos_list' => $kosList->map(fn ($k) => [
                'id' => $k->id,
                'name' => $k->name,
                'city' => $k->city,
                'status' => $k->status,
                'verified' => ! empty($k->verified_at),
                'total_rooms' => $k->total_rooms,
                'occupied_rooms' => $k->occupied_rooms,
                'available_rooms' => $k->total_rooms - $k->occupied_rooms,
            ]),
        ];
    }

    /** Tool: get_my_wallet (pemilik) */
    protected function toolGetMyWallet(?User $sender): array
    {
        if (! $sender) {
            return [
                'error' => 'User tidak teridentifikasi',
                'message_to_ai' => 'Nomor WhatsApp pengirim tidak cocok dengan data di sistem. Arahkan untuk update nomor WA di halaman Profil website.',
            ];
        }

        $wallet = $sender->wallet;
        $pendingWithdrawals = $sender->withdrawalRequests()->where('status', 'menunggu_persetujuan')->count();
        $minWithdrawal = (float) Setting::getSetting('min_withdrawal', 50000);

        return [
            'available_balance' => $wallet ? (float) $wallet->available_balance : 0,
            'pending_withdrawal_balance' => $wallet ? (float) $wallet->pending_withdrawal_balance : 0,
            'pending_withdrawal_requests' => $pendingWithdrawals,
            'min_withdrawal' => $minWithdrawal,
            'message_to_ai' => 'Tampilkan saldo dalam format Rupiah yang rapi. Jelaskan juga batas minimal penarikan. Jika ada withdrawal pending, sebutkan jumlahnya.',
        ];
    }

    /** Tool: get_my_tenants (pemilik) */
    protected function toolGetMyTenants(?User $sender): array
    {
        if (! $sender) {
            return [
                'error' => 'User tidak teridentifikasi',
                'message_to_ai' => 'Nomor WhatsApp pengirim tidak cocok dengan data di sistem. Arahkan untuk update nomor WA di halaman Profil website.',
            ];
        }

        $tenancies = Tenancy::where('admin_id', $sender->id)
            ->where('status', 'aktif')
            ->with(['user', 'room.boardingHouse'])
            ->limit(20)
            ->get();

        if ($tenancies->isEmpty()) {
            return [
                'count' => 0,
                'tenants' => [],
                'message_to_ai' => 'Belum ada penyewa aktif di kos milik pemilik ini. Beritahu dengan ramah.',
            ];
        }

        return [
            'count' => $tenancies->count(),
            'tenants' => $tenancies->map(fn ($t) => [
                'tenant_name' => $t->user?->name,
                'kos_name' => $t->boardingHouse?->name,
                'room_number' => $t->room?->room_number,
                'start_date' => $t->start_date?->format('Y-m-d'),
                'end_date' => $t->end_date?->format('Y-m-d'),
                'occupant_count' => $t->occupant_count,
            ]),
        ];
    }

    /** Tool: get_my_reviews (pemilik) */
    protected function toolGetMyReviews(?User $sender): array
    {
        if (! $sender) {
            return [
                'error' => 'User tidak teridentifikasi',
                'message_to_ai' => 'Nomor WhatsApp pengirim tidak cocok dengan data di sistem. Arahkan untuk update nomor WA di halaman Profil website.',
            ];
        }

        $kosIds = BoardingHouse::where('admin_id', $sender->id)->pluck('id');

        if ($kosIds->isEmpty()) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'recent_reviews' => [],
                'message_to_ai' => 'Pemilik belum memiliki kos terdaftar sehingga belum ada ulasan. Beritahu dengan ramah.',
            ];
        }

        $reviews = \App\Models\BoardingHouseReview::whereIn('boarding_house_id', $kosIds)
            ->with(['user', 'boardingHouse'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $avgRating = $reviews->isNotEmpty() ? round($reviews->avg('rating'), 1) : 0;

        if ($reviews->isEmpty()) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'recent_reviews' => [],
                'message_to_ai' => 'Belum ada ulasan dari penyewa untuk kos milik pemilik ini. Beritahu dengan ramah bahwa belum ada review masuk.',
            ];
        }

        return [
            'total_reviews' => $reviews->count(),
            'average_rating' => $avgRating,
            'recent_reviews' => $reviews->take(3)->map(fn ($r) => [
                'rating' => $r->rating,
                'comment' => \Illuminate\Support\Str::limit($r->comment, 150),
                'tenant_name' => $r->user?->name,
                'kos_name' => $r->boardingHouse?->name,
                'date' => $r->created_at?->format('Y-m-d'),
            ]),
        ];
    }

    /** Tool: get_platform_fees (pemilik/public) */
    protected function toolGetPlatformFees(): array
    {
        return [
            'ppn_percent' => (float) Setting::getSetting('ppn_percent', 11),
            'pph_percent' => (float) Setting::getSetting('pph_percent', 10),
            'min_withdrawal' => (float) Setting::getSetting('min_withdrawal', 50000),
            'explanation' => [
                'ppn' => 'PPN (Pajak Pertambahan Nilai) dikenakan kepada penyewa dan ditambahkan ke setiap invoice pembayaran sewa.',
                'pph' => 'PPh (Pajak Penghasilan) dipotong dari saldo pemilik kos saat melakukan penarikan/withdrawal.',
                'min_withdrawal' => 'Penarikan saldo hanya bisa dilakukan jika saldo mencapai batas minimal.',
            ],
            'message_to_ai' => 'Jelaskan PPN dan PPh dengan bahasa sederhana dan mudah dipahami. Sebutkan persentase dan batas minimal penarikan dalam format Rupiah.',
        ];
    }

    /** Tool: get_featured_kos (public) — 5 kos featured */
    protected function toolGetFeaturedKos(): array
    {
        $appUrl = rtrim(config('app.url'), '/');

        $kosList = BoardingHouse::where('status', 'dipublikasikan')
            ->with(['rooms' => fn ($q) => $q->where('status', 'tersedia')->orderBy('price')])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        if ($kosList->isEmpty()) {
            return [
                'count' => 0,
                'kos_list' => [],
                'message_to_ai' => 'Belum ada kos yang dipublikasikan saat ini. Beritahu pengguna dengan ramah dan sarankan untuk cek kembali nanti.',
            ];
        }

        return [
            'count' => $kosList->count(),
            'kos_list' => $kosList->map(fn ($k) => [
                'name' => $k->name,
                'city' => $k->city,
                'district' => $k->district,
                'starting_price' => $k->rooms->isNotEmpty() ? (float) $k->rooms->first()->price : null,
                'price_period' => $k->rooms->first()?->price_period,
                'available_rooms' => $k->rooms->count(),
                'detail_url' => $appUrl . '/kos/' . $k->id,
            ]),
            'message_to_ai' => 'Tampilkan daftar kos rekomendasi dengan format rapi berisi nama, lokasi, harga, dan link detail. Gunakan format Rupiah untuk harga.',
        ];
    }

    /** Tool: get_platform_info (public) */
    protected function toolGetPlatformInfo(): array
    {
        $publishedKos = BoardingHouse::where('status', 'dipublikasikan')->count();
        $availableRooms = \App\Models\Room::where('status', 'tersedia')->count();
        $appName = Setting::getSetting('app_name', 'CariKosanMu');
        $appUrl = config('app.url');

        return [
            'app_name' => $appName,
            'app_url' => $appUrl,
            'about_us' => \Illuminate\Support\Str::limit(Setting::getSetting('about_us', ''), 500),
            'contact_email' => Setting::getSetting('contact_email'),
            'contact_phone' => Setting::getSetting('contact_phone'),
            'total_published_kos' => $publishedKos,
            'total_available_rooms' => $availableRooms,
            'register_url' => $appUrl . '/register',
            'how_it_works' => "Platform {$appName} memudahkan pencarian dan pengelolaan kos-kosan secara online. Penyewa mencari kos → pilih kamar → bayar online → mulai ngekos. Pemilik mendaftarkan kos → diverifikasi admin → dipublikasikan → terima pembayaran otomatis.",
            'how_to_register' => "Kunjungi {$appUrl}/register, isi data diri, verifikasi email, dan akun siap digunakan. Bisa juga login menggunakan Google.",
            'message_to_ai' => 'Gunakan data ini untuk menjawab pertanyaan tentang platform. Sebutkan jumlah kos dan kamar yang tersedia sebagai bukti bahwa platform aktif. JANGAN mengarang informasi tambahan di luar data ini.',
        ];
    }

    /** Tool: search_kos_by_keyword (public & user) */
    protected function toolSearchKosByKeyword(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [
                'error' => 'Keyword pencarian kosong',
                'message_to_ai' => 'Pengguna tidak memberikan kata kunci pencarian. Tanyakan kembali daerah atau lokasi kos yang mereka cari.',
            ];
        }

        $appUrl = rtrim(config('app.url'), '/');
        $term = '%' . strtolower($keyword) . '%';

        $kosList = BoardingHouse::where('status', 'dipublikasikan')
            ->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(address) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(city) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(district) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(subdistrict) LIKE ?', [$term]);
            })
            ->with(['rooms' => fn ($q) => $q->where('status', 'tersedia')->orderBy('price')])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        if ($kosList->isEmpty()) {
            return [
                'count' => 0,
                'keyword' => $keyword,
                'message_to_ai' => "Tidak ditemukan kos untuk kata kunci \"{$keyword}\". Beritahu pengguna dengan sopan bahwa kos di daerah/kata kunci tersebut belum tersedia dan tawarkan untuk mencari di daerah lain.",
            ];
        }

        return [
            'count' => $kosList->count(),
            'keyword' => $keyword,
            'kos_list' => $kosList->map(fn ($k) => [
                'name' => $k->name,
                'city' => $k->city,
                'district' => $k->district,
                'address' => \Illuminate\Support\Str::limit($k->address, 120),
                'starting_price' => $k->rooms->isNotEmpty() ? (float) $k->rooms->first()->price : null,
                'price_period' => $k->rooms->first()?->price_period,
                'available_rooms' => $k->rooms->count(),
                'detail_url' => $appUrl . '/kos/' . $k->id,
            ]),
            'message_to_ai' => 'Tampilkan hasil pencarian kos dengan format rapi: nama, lokasi, harga mulai dari, jumlah kamar tersedia, dan link detail. Gunakan format Rupiah untuk harga.',
        ];
    }
}
