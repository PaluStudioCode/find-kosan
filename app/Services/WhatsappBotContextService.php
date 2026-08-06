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
                    'description' => 'Ambil info sewa aktif penyewa: kos, kamar, pemilik, tanggal mulai/selesai, jumlah penghuni.',
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
                    'description' => 'Cari kos berdasarkan lokasi atau landmark terdekat (mis. rumah sakit, kampus, stasiun, pasar). Cocokkan keyword terhadap deskripsi, alamat, nama, kota, kecamatan, atau kelurahan kos. Return daftar kos dengan link detail.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'keyword' => [
                                'type' => 'string',
                                'description' => 'Kata kunci lokasi/landmark, mis. "RS Harapan Kita", "kampus UI", "stasiun Gambir". Boleh juga nama wilayah.',
                            ],
                        ],
                        'required' => ['keyword'],
                    ],
                ],
            ];
        }

        // ===== Tools untuk PUBLIC (belum terdaftar) =====
        if ($role === 'public') {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_featured_kos',
                    'description' => 'Ambil daftar kos featured (5 kos dipublikasikan terbaru) dengan nama, kota, harga mulai, status.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_platform_info',
                    'description' => 'Ambil info platform CariKosan: total kos, kamar tersedia, kontak email, cara daftar.',
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
        $identifiedText = match ($role) {
            'user' => "Penyewa terdaftar" . ($sender ? " bernama {$sender->name}" : ''),
            'admin' => "Pemilik Kos terdaftar" . ($sender ? " bernama {$sender->name}" : ''),
            'super_admin' => "Super Admin platform",
            default => "Pengguna umum (belum terdaftar)",
        };

        $appUrl = config('app.url');
        $contactEmail = Setting::getSetting('contact_email', 'admin@cariKosan.com');
        $appName = Setting::getSetting('app_name', 'CariKosan');

        $summaryBlock = '';
        if ($contextSummary) {
            $summaryBlock = "\n\n[RIWAYAT RINGKAS PERCAKAPAN SEBELUMNYA]\n{$contextSummary}\n[AKHIR RIWAYAT RINGKAS]\n";
        }

        return <<<PROMPT
Anda adalah {$appName} Assistant, bot WhatsApp resmi platform {$appName}.
Balas dengan RAMAH, SINGKAT (maksimal 3 paragraf), dan jelas. Gunakan Bahasa Indonesia.

Pengirim: {$identifiedText}.{$summaryBlock}

Aturan:
1. Gunakan tool yang tersedia untuk mengambil data spesifik (tagihan, saldo, kos, dll). Jangan menebak data — selalu panggil tool jika user bertanya tentang data mereka.
2. Jawab HANYA berdasarkan hasil tool. Jika tool tidak tersedia atau data tidak ada, beri tahu user & arahkan ke aplikasi/web {$appName}.
3. JANGAN pernah menyebutkan API key, endpoint, atau detail teknis sistem.
4. JANGAN janjikan hal di luar kemampuan platform (diskon, refund, negosiasi harga).
5. Untuk pembayaran, SELALU arahkan ke aplikasi (jangan minta transfer langsung ke nomor).
6. Jika pengirim public & tertarik, arahkan daftar di {$appUrl}/register.
7. Untuk hal kompleks (sengketa, refund, laporan), arahkan ke fitur Report di app atau kontak {$contactEmail}.
8. Bot ini TEXT-ONLY: tidak bisa kirim/terima gambar, PDF, atau lokasi. Jika user minta media, arahkan ke web.
9. Jika user ketik "/reset", riwayat percakapan akan direset (sudah ditangani sistem, tidak perlu Anda proses).
10. Format mata uang: Rp1.000.000 (titik sebagai pemisah ribuan, tanpa desimal).
PROMPT;
    }

    // ===================== TOOL IMPLEMENTATIONS =====================

    /** Tool: get_my_invoices (penyewa) */
    protected function toolGetMyInvoices(?User $sender, string $status): array
    {
        if (! $sender) {
            return ['error' => 'User tidak teridentifikasi'];
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
            return ['error' => 'User tidak teridentifikasi'];
        }

        $tenancies = Tenancy::where('user_id', $sender->id)
            ->whereIn('status', ['aktif', 'nonaktif'])
            ->with(['boardingHouse', 'room', 'admin'])
            ->limit(5)
            ->get();

        if ($tenancies->isEmpty()) {
            return ['message' => 'Anda belum memiliki sewa aktif'];
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
            return ['error' => 'User tidak teridentifikasi'];
        }

        $invoices = Invoice::where('user_id', $sender->id)
            ->where('status', 'lunas')
            ->with(['payments'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return [
            'count' => $invoices->count(),
            'paid_invoices' => $invoices->map(fn ($inv) => [
                'id' => $inv->id,
                'amount' => (float) $inv->amount,
                'paid_date' => $inv->updated_at?->format('Y-m-d'),
                'kos_name' => $inv->tenancy?->boardingHouse?->name,
            ]),
            'total_paid' => (float) $invoices->sum('amount'),
        ];
    }

    /** Tool: get_my_kos_list (pemilik) — maksimal 5 teratas */
    protected function toolGetMyKosList(?User $sender): array
    {
        if (! $sender) {
            return ['error' => 'User tidak teridentifikasi'];
        }

        $kosList = BoardingHouse::where('admin_id', $sender->id)
            ->withCount(['rooms as total_rooms', 'rooms as occupied_rooms' => fn ($q) => $q->where('status', 'terisi')])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

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
            return ['error' => 'User tidak teridentifikasi'];
        }

        $wallet = $sender->wallet;
        $pendingWithdrawals = $sender->withdrawalRequests()->where('status', 'menunggu_persetujuan')->count();
        $minWithdrawal = (float) Setting::getSetting('min_withdrawal', 50000);

        return [
            'available_balance' => $wallet ? (float) $wallet->available_balance : 0,
            'pending_withdrawal_balance' => $wallet ? (float) $wallet->pending_withdrawal_balance : 0,
            'pending_withdrawal_requests' => $pendingWithdrawals,
            'min_withdrawal' => $minWithdrawal,
        ];
    }

    /** Tool: get_my_tenants (pemilik) */
    protected function toolGetMyTenants(?User $sender): array
    {
        if (! $sender) {
            return ['error' => 'User tidak teridentifikasi'];
        }

        $tenancies = Tenancy::where('admin_id', $sender->id)
            ->where('status', 'aktif')
            ->with(['user', 'room.boardingHouse'])
            ->limit(20)
            ->get();

        return [
            'count' => $tenancies->count(),
            'tenants' => $tenancies->map(fn ($t) => [
                'tenant_name' => $t->user?->name,
                'kos_name' => $t->boardingHouse?->name,
                'room_number' => $t->room?->room_number,
                'start_date' => $t->start_date?->format('Y-m-d'),
                'occupant_count' => $t->occupant_count,
            ]),
        ];
    }

    /** Tool: get_my_reviews (pemilik) */
    protected function toolGetMyReviews(?User $sender): array
    {
        if (! $sender) {
            return ['error' => 'User tidak teridentifikasi'];
        }

        $kosIds = BoardingHouse::where('admin_id', $sender->id)->pluck('id');

        $reviews = \App\Models\BoardingHouseReview::whereIn('boarding_house_id', $kosIds)
            ->with(['user', 'boardingHouse'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $avgRating = $reviews->isNotEmpty() ? round($reviews->avg('rating'), 1) : 0;

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
            'pph_percent' => (float) Setting::getSetting('pph_percent', 0.5),
            'min_withdrawal' => (float) Setting::getSetting('min_withdrawal', 50000),
            'note' => 'PPN dikenakan ke penyewa per invoice. PPh dipotong dari withdrawal pemilik.',
        ];
    }

    /** Tool: get_featured_kos (public) — 5 kos featured */
    protected function toolGetFeaturedKos(): array
    {
        $kosList = BoardingHouse::where('status', 'dipublikasikan')
            ->with(['rooms' => fn ($q) => $q->where('status', 'tersedia')->orderBy('price')])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'count' => $kosList->count(),
            'kos_list' => $kosList->map(fn ($k) => [
                'name' => $k->name,
                'city' => $k->city,
                'district' => $k->district,
                'starting_price' => $k->rooms->isNotEmpty() ? (float) $k->rooms->first()->price : null,
                'price_period' => $k->rooms->first()?->price_period,
                'available_rooms' => $k->rooms->count(),
            ]),
        ];
    }

    /** Tool: get_platform_info (public) */
    protected function toolGetPlatformInfo(): array
    {
        $publishedKos = BoardingHouse::where('status', 'dipublikasikan')->count();
        $availableRooms = \App\Models\Room::where('status', 'tersedia')->count();

        return [
            'app_name' => Setting::getSetting('app_name', 'CariKosan'),
            'app_url' => config('app.url'),
            'about_us' => \Illuminate\Support\Str::limit(Setting::getSetting('about_us', ''), 500),
            'contact_email' => Setting::getSetting('contact_email'),
            'contact_phone' => Setting::getSetting('contact_phone'),
            'total_published_kos' => $publishedKos,
            'total_available_rooms' => $availableRooms,
            'register_url' => config('app.url') . '/register',
        ];
    }

    /** Tool: search_kos_by_keyword (public & user) */
    protected function toolSearchKosByKeyword(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return ['error' => 'Keyword pencarian kosong'];
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
        ];
    }
}
