<?php

namespace App\Services;

use App\Helpers\WhatsappNumber;
use App\Models\AdminWallet;
use App\Models\BoardingHouse;
use App\Models\BoardingHouseReview;
use App\Models\Invoice;
use App\Models\Tenancy;
use App\Models\User;
use App\Models\WaBotConversation;
use App\Models\WaBotMessage;
use Illuminate\Support\Facades\Log;

class WhatsappBotService
{
    protected const MAX_TOOL_ITERATIONS = 5;

    public function __construct(
        protected NineRouterService $nineRouter,
    ) {}

    public function identifySender(string $fromJid): array
    {
        $user = null;
        $role = 'public';
        $phone = null;

        if (str_ends_with($fromJid, '@s.whatsapp.net')) {
            $raw = str_replace('@s.whatsapp.net', '', $fromJid);
            $phone = WhatsappNumber::normalize($raw);

            if ($phone) {
                $user = User::where('whatsapp_number', $phone)
                    ->where('status', 'aktif')
                    ->first();

                if ($user) {
                    $role = $user->role;
                }
            }
        }

        return compact('user', 'role', 'phone');
    }

    public function upsertConversation(string $fromJid, ?User $user, string $role, ?string $phone): WaBotConversation
    {
        return WaBotConversation::updateOrCreate(
            ['from_jid' => $fromJid],
            [
                'phone_number' => $phone ?? null,
                'user_id' => $user?->id,
                'identified_role' => $role,
            ]
        );
    }

    public function generateReply(WaBotConversation $conversation, ?User $sender, string $role, string $userText): string
    {
        try {
            $systemPrompt = $this->buildSystemPrompt($sender, $role, $conversation->context_summary);
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userText],
            ];

            $tools = $this->getToolsForRole($role);

            $iteration = 0;
            while ($iteration < self::MAX_TOOL_ITERATIONS) {
                $iteration++;

                Log::info('[WA Bot] LLM call', [
                    'conversation_id' => $conversation->id,
                    'iteration' => $iteration,
                    'messages_count' => count($messages),
                ]);

                $response = $this->nineRouter->chatWithTools($messages, $tools);
                $tokensUsed = $response['tokens'];
                $modelUsed = $response['model'];

                if (! empty($response['tool_calls'])) {
                    $assistantMsg = WaBotMessage::create([
                        'conversation_id' => $conversation->id,
                        'role' => 'assistant',
                        'content' => $response['content'],
                        'tool_calls' => $response['tool_calls'],
                        'tokens_used' => $tokensUsed,
                        'model_used' => $modelUsed,
                    ]);
                    $messages[] = $assistantMsg->toOpenAiFormat();

                    foreach ($response['tool_calls'] as $toolCall) {
                        $toolName = $toolCall['function']['name'] ?? '';
                        $arguments = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];
                        $toolCallId = $toolCall['id'] ?? '';

                        Log::info('[WA Bot] Tool call', [
                            'tool' => $toolName,
                            'arguments' => $arguments,
                            'conversation_id' => $conversation->id,
                        ]);

                        $toolResult = $this->executeTool($toolName, $arguments, $sender);

                        WaBotMessage::create([
                            'conversation_id' => $conversation->id,
                            'role' => 'tool',
                            'content' => $toolResult,
                            'tool_call_id' => $toolCallId,
                            'tool_name' => $toolName,
                        ]);

                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCallId,
                            'content' => $toolResult,
                        ];
                    }

                    continue;
                }

                $finalReply = $response['content'] ?? '';
                if (trim($finalReply) === '') {
                    $finalReply = 'Maaf, saya tidak bisa memproses permintaan Anda saat ini. Silakan coba lagi.';
                }

                WaBotMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $finalReply,
                    'tokens_used' => $tokensUsed,
                    'model_used' => $modelUsed,
                ]);
                $conversation->touchActivity();

                return $finalReply;
            }

            return 'Maaf, permintaan Anda terlalu kompleks. Mohon sederhanakan pertanyaan Anda.';
        } catch (NineRouterException $e) {
            Log::error('[WA Bot] LLM error', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return 'Maaf, bot sedang mengalami gangguan. Silakan coba lagi nanti.';
        } catch (\Exception $e) {
            Log::error('[WA Bot] Reply failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return 'Maaf, terjadi kesalahan. Silakan coba lagi nanti.';
        }
    }

    public function buildSystemPrompt(?User $sender, string $role, ?string $contextSummary): string
    {
        $appName = 'CariKosanMu';
        $appUrl = 'https://carikosanmu.online';
        $contactEmail = 'support@carikosanmu.online';
        $contactPhone = '+62 853-4100-1664';

        $senderInfo = match (true) {
            $role === 'super_admin' && $sender => "Super Admin platform bernama *{$sender->name}*.",
            $role === 'admin' && $sender => "Pemilik kos terdaftar bernama *{$sender->name}*.",
            $role === 'user' && $sender => "Penyewa terdaftar bernama *{$sender->name}*.",
            default => 'Pengguna publik (belum terdaftar atau nomor tidak dikenali).',
        };

        $contextBlock = '';
        if ($contextSummary) {
            $contextBlock = <<<CONTEXT

[RINGKASAN PERCAKAPAN SEBELUMNYA]
{$contextSummary}
[AKHIR RINGKASAN]
Ringkasan di atas hanya untuk konteks. JANGAN gunakan data dari ringkasan untuk menjawab pertanyaan baru. Selalu panggil tool untuk data terbaru.
CONTEXT;
        }

        $roleInstructions = match ($role) {
            'user' => <<<'ROLE'
Pengguna ini adalah PENYEWA. Anda bisa membantu:
- Mencari kos (search_kos_by_keyword, get_featured_kos)
- Cek tagihan (get_my_invoices)
- Info kos aktif & kontak pemilik (get_my_tenancy)
- Riwayat pembayaran (get_payment_history)
- Info platform (get_platform_info)
Untuk pembayaran, arahkan ke website.
ROLE,
            'admin' => <<<'ROLE'
Pengguna ini adalah PEMILIK KOS. Anda bisa membantu:
- Daftar kos miliknya (get_my_kos_list)
- Info wallet & withdrawal (get_my_wallet)
- Daftar penyewa aktif (get_my_tenants)
- Review dari penyewa (get_my_reviews)
- Info biaya platform/pajak (get_platform_fees)
Untuk pengelolaan kos detail, arahkan ke dashboard di website.
ROLE,
            'super_admin' => <<<'ROLE'
Pengguna ini adalah SUPER ADMIN. Anda bisa membantu dengan semua fitur admin dan user.
ROLE,
            default => <<<'ROLE'
Pengguna ini BELUM TERDAFTAR. Anda bisa membantu:
- Mencari kos (search_kos_by_keyword, get_featured_kos)
- Info platform (get_platform_info)
Untuk fitur lainnya, sarankan mendaftar di website.
ROLE,
        };

        return <<<PROMPT
Anda adalah *Asisten {$appName}*, Customer Service profesional untuk platform kos-kosan online.

Pengirim pesan ini: {$senderInfo}
{$contextBlock}

══════════════════════════════════
1. PROTOKOL ANTI-HALUSINASI
══════════════════════════════════
- DILARANG KERAS mengarang, mengasumsikan, atau menebak data kos, harga, ketersediaan kamar, tagihan, atau informasi apapun.
- Untuk SETIAP pertanyaan yang membutuhkan data → WAJIB panggil tool yang sesuai.
- Jika tool mengembalikan hasil kosong → katakan "tidak ditemukan" dan tawarkan alternatif.
- JANGAN PERNAH menyebut nama kos, harga, atau alamat yang tidak berasal dari hasil tool.

══════════════════════════════════
2. BATASAN DOMAIN
══════════════════════════════════
- Hanya jawab pertanyaan seputar kos-kosan, platform {$appName}, dan layanan terkait.
- Untuk topik di luar domain → tolak dengan sopan: "Maaf, saya hanya bisa membantu seputar kos-kosan dan platform {$appName}."

══════════════════════════════════
3. ATURAN BISNIS
══════════════════════════════════
- Pembayaran HANYA bisa dilakukan melalui website {$appUrl}. Bot tidak bisa memproses pembayaran.
- Harga kos TIDAK BISA dinegosiasi melalui bot.
- Bot hanya bisa mengirim pesan teks (tidak bisa kirim gambar/dokumen).

══════════════════════════════════
4. FAQ PLATFORM
══════════════════════════════════
- "Apa itu {$appName}?" → Platform digital pencarian dan pengelolaan kos-kosan online. Penyewa bisa cari kos, lihat detail & foto, dan bayar online. Pemilik kos bisa daftarkan properti, kelola penyewa, dan terima pembayaran.
- "Bagaimana cara daftar?" → Kunjungi {$appUrl}, klik Daftar, pilih role (Penyewa/Pemilik Kos).
- "Bagaimana cara bayar sewa?" → Login di {$appUrl}, buka tagihan, pilih metode pembayaran.
- "Bagaimana cara daftarkan kos?" → Daftar sebagai Pemilik Kos di {$appUrl}, lalu tambahkan kos dari dashboard.

══════════════════════════════════
5. GAYA KOMUNIKASI
══════════════════════════════════
- Bahasa Indonesia yang ramah, natural, dan ringkas.
- Panggil pengguna "Kak".
- Emoji secukupnya (1-2 per pesan).
- Format daftar data dengan nomor urut yang rapi.
- Akhiri dengan tawaran bantuan singkat (variasikan kalimatnya).

══════════════════════════════════
6. ATURAN ANTI-PENGULANGAN (KRITIS)
══════════════════════════════════
- Selalu jawab berdasarkan PESAN USER TERAKHIR (paling bawah).
- JANGAN ulangi jawaban yang sudah diberikan sebelumnya.
- Jika user minta CARI KOS → langsung panggil tool, JANGAN jelaskan apa itu platform.
- Jika user minta REKOMENDASI → langsung panggil tool, JANGAN jelaskan apa itu platform.
- DILARANG menjelaskan apa itu {$appName} KECUALI user secara eksplisit bertanya.
- Untuk SETIAP pencarian baru → WAJIB panggil tool dengan keyword baru. DILARANG daur ulang hasil sebelumnya.
- Pesan "[Sudah saya jawab sebelumnya]" berarti topik itu SELESAI. Fokus pada pertanyaan terbaru.

══════════════════════════════════
7. PENANGANAN KELUHAN
══════════════════════════════════
Jika penyewa mengeluh (fasilitas rusak, masalah keamanan, dsb):
1. Tunjukkan empati.
2. Panggil tool 'get_my_tenancy' untuk kontak pemilik kos.
3. Berikan kontak pemilik agar penyewa bisa menghubungi langsung.
4. Jika kontak tidak ditemukan → arahkan ke email {$contactEmail}.

══════════════════════════════════
8. INSTRUKSI KHUSUS ROLE
══════════════════════════════════
{$roleInstructions}

══════════════════════════════════
KONTAK PLATFORM
══════════════════════════════════
- Website: {$appUrl}
- Email: {$contactEmail}
- Telepon: {$contactPhone}
PROMPT;
    }

    public function getToolsForRole(string $role): array
    {
        $tools = [];

        if (in_array($role, ['public', 'user', 'super_admin'])) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'search_kos_by_keyword',
                    'description' => 'Cari kos berdasarkan keyword (nama, alamat, kota, kecamatan, kelurahan). Gunakan tool ini setiap kali user meminta pencarian kos.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'keyword' => [
                                'type' => 'string',
                                'description' => 'Keyword pencarian (nama kos, alamat, kota, kecamatan, atau kelurahan)',
                            ],
                        ],
                        'required' => ['keyword'],
                    ],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_featured_kos',
                    'description' => 'Ambil daftar kos unggulan/terbaru yang dipublikasikan. Gunakan jika user minta rekomendasi tanpa keyword spesifik.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_platform_info',
                    'description' => 'Ambil informasi dan statistik platform (jumlah kos, jumlah user, dsb).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ];
        }

        if (in_array($role, ['user', 'super_admin'])) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_invoices',
                    'description' => 'Ambil daftar tagihan penyewa. Bisa difilter berdasarkan status.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type' => 'string',
                                'enum' => ['belum_dibayar', 'jatuh_tempo', 'menunggu_konfirmasi', 'lunas', 'semua'],
                                'description' => 'Filter status tagihan. Default: semua.',
                            ],
                        ],
                    ],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_tenancy',
                    'description' => 'Ambil info kos aktif penyewa beserta kontak pemilik kos.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_payment_history',
                    'description' => 'Ambil riwayat pembayaran sewa penyewa.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ];
        }

        if (in_array($role, ['admin', 'super_admin'])) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_kos_list',
                    'description' => 'Ambil daftar kos milik pemilik beserta statistik kamar.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_wallet',
                    'description' => 'Ambil info saldo wallet dan riwayat withdrawal pemilik kos.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_tenants',
                    'description' => 'Ambil daftar penyewa aktif di semua kos milik pemilik.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_reviews',
                    'description' => 'Ambil ringkasan review dan rating dari penyewa.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'get_platform_fees',
                    'description' => 'Ambil informasi biaya platform (PPN, PPh).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ];
        }

        return $tools;
    }

    public function executeTool(string $toolName, array $args, ?User $sender): string
    {
        try {
            $result = match ($toolName) {
                'search_kos_by_keyword' => $this->toolSearchKos($args['keyword'] ?? ''),
                'get_featured_kos' => $this->toolFeaturedKos(),
                'get_platform_info' => $this->toolPlatformInfo(),
                'get_my_invoices' => $this->toolMyInvoices($sender, $args['status'] ?? 'semua'),
                'get_my_tenancy' => $this->toolMyTenancy($sender),
                'get_payment_history' => $this->toolPaymentHistory($sender),
                'get_my_kos_list' => $this->toolMyKosList($sender),
                'get_my_wallet' => $this->toolMyWallet($sender),
                'get_my_tenants' => $this->toolMyTenants($sender),
                'get_my_reviews' => $this->toolMyReviews($sender),
                'get_platform_fees' => $this->toolPlatformFees(),
                default => json_encode(['error' => 'Tool tidak dikenali: '.$toolName]),
            };

            return $result;
        } catch (\Exception $e) {
            Log::error('[WA Bot] Tool execution failed', [
                'tool' => $toolName,
                'error' => $e->getMessage(),
            ]);

            return json_encode(['error' => 'Gagal mengeksekusi tool: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    protected function toolSearchKos(string $keyword): string
    {
        $appUrl = config('app.url', 'https://carikosanmu.online');
        $kos = BoardingHouse::where('status', 'dipublikasikan')
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('address', 'like', "%{$keyword}%")
                    ->orWhere('city', 'like', "%{$keyword}%")
                    ->orWhere('district', 'like', "%{$keyword}%")
                    ->orWhere('subdistrict', 'like', "%{$keyword}%");
            })
            ->with(['rooms' => fn ($q) => $q->where('status', 'tersedia')])
            ->limit(5)
            ->get();

        if ($kos->isEmpty()) {
            return json_encode(['results' => [], 'message' => "Tidak ditemukan kos dengan keyword \"{$keyword}\"."], JSON_UNESCAPED_UNICODE);
        }

        $results = $kos->map(fn ($k) => [
            'nama' => $k->name,
            'alamat' => $k->address,
            'kota' => $k->city,
            'kecamatan' => $k->district,
            'harga_mulai' => $k->rooms->min('price') ? 'Rp'.number_format($k->rooms->min('price'), 0, ',', '.') : 'Hubungi pemilik',
            'kamar_tersedia' => $k->rooms->count(),
            'link' => "{$appUrl}/kos/{$k->id}",
        ])->toArray();

        return json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
    }

    protected function toolFeaturedKos(): string
    {
        $appUrl = config('app.url', 'https://carikosanmu.online');
        $kos = BoardingHouse::where('status', 'dipublikasikan')
            ->with(['rooms' => fn ($q) => $q->where('status', 'tersedia')])
            ->latest()
            ->limit(5)
            ->get();

        if ($kos->isEmpty()) {
            return json_encode(['results' => [], 'message' => 'Belum ada kos yang dipublikasikan.'], JSON_UNESCAPED_UNICODE);
        }

        $results = $kos->map(fn ($k) => [
            'nama' => $k->name,
            'alamat' => $k->address,
            'kota' => $k->city,
            'harga_mulai' => $k->rooms->min('price') ? 'Rp'.number_format($k->rooms->min('price'), 0, ',', '.') : 'Hubungi pemilik',
            'kamar_tersedia' => $k->rooms->count(),
            'link' => "{$appUrl}/kos/{$k->id}",
        ])->toArray();

        return json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
    }

    protected function toolPlatformInfo(): string
    {
        return json_encode([
            'nama_platform' => 'CariKosanMu',
            'website' => 'https://carikosanmu.online',
            'total_kos' => BoardingHouse::where('status', 'dipublikasikan')->count(),
            'total_user' => User::where('status', 'aktif')->count(),
            'total_pemilik_kos' => User::where('role', 'admin')->where('status', 'aktif')->count(),
        ], JSON_UNESCAPED_UNICODE);
    }

    protected function toolMyInvoices(?User $sender, string $status): string
    {
        if (! $sender) {
            return json_encode(['error' => 'Anda harus terdaftar untuk melihat tagihan.'], JSON_UNESCAPED_UNICODE);
        }

        $query = Invoice::where('user_id', $sender->id);
        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        $invoices = $query->with('tenancy.boardingHouse')
            ->latest('due_date')
            ->limit(10)
            ->get();

        if ($invoices->isEmpty()) {
            return json_encode(['results' => [], 'message' => 'Tidak ada tagihan ditemukan.'], JSON_UNESCAPED_UNICODE);
        }

        $results = $invoices->map(fn ($inv) => [
            'kos' => $inv->tenancy?->boardingHouse?->name ?? '-',
            'periode' => $inv->period_start->format('d/m/Y').' - '.$inv->period_end->format('d/m/Y'),
            'jumlah' => 'Rp'.number_format($inv->amount, 0, ',', '.'),
            'jatuh_tempo' => $inv->due_date->format('d/m/Y'),
            'status' => $inv->status,
        ])->toArray();

        return json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
    }

    protected function toolMyTenancy(?User $sender): string
    {
        if (! $sender) {
            return json_encode(['error' => 'Anda harus terdaftar untuk melihat info kos.'], JSON_UNESCAPED_UNICODE);
        }

        $tenancy = Tenancy::where('user_id', $sender->id)
            ->where('status', 'aktif')
            ->with(['boardingHouse.admin', 'room'])
            ->first();

        if (! $tenancy) {
            return json_encode(['message' => 'Anda belum memiliki kos aktif.'], JSON_UNESCAPED_UNICODE);
        }

        $owner = $tenancy->boardingHouse?->admin;

        return json_encode([
            'kos' => $tenancy->boardingHouse?->name,
            'alamat' => $tenancy->boardingHouse?->address,
            'kamar' => $tenancy->room?->room_number,
            'harga' => 'Rp'.number_format($tenancy->room?->price ?? 0, 0, ',', '.').'/'.($tenancy->room?->price_period ?? 'bulan'),
            'mulai_sewa' => $tenancy->start_date->format('d/m/Y'),
            'pemilik' => $owner?->name ?? '-',
            'wa_pemilik' => $owner?->whatsapp_number ?? '-',
        ], JSON_UNESCAPED_UNICODE);
    }

    protected function toolPaymentHistory(?User $sender): string
    {
        if (! $sender) {
            return json_encode(['error' => 'Anda harus terdaftar untuk melihat riwayat pembayaran.'], JSON_UNESCAPED_UNICODE);
        }

        $invoices = Invoice::where('user_id', $sender->id)
            ->where('status', 'lunas')
            ->with('tenancy.boardingHouse')
            ->latest('period_end')
            ->limit(10)
            ->get();

        if ($invoices->isEmpty()) {
            return json_encode(['results' => [], 'message' => 'Belum ada riwayat pembayaran.'], JSON_UNESCAPED_UNICODE);
        }

        $results = $invoices->map(fn ($inv) => [
            'kos' => $inv->tenancy?->boardingHouse?->name ?? '-',
            'periode' => $inv->period_start->format('d/m/Y').' - '.$inv->period_end->format('d/m/Y'),
            'jumlah' => 'Rp'.number_format($inv->amount, 0, ',', '.'),
            'status' => 'lunas',
        ])->toArray();

        return json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
    }

    protected function toolMyKosList(?User $sender): string
    {
        if (! $sender) {
            return json_encode(['error' => 'Anda harus terdaftar sebagai pemilik kos.'], JSON_UNESCAPED_UNICODE);
        }

        $kosList = BoardingHouse::where('admin_id', $sender->id)
            ->withCount([
                'rooms',
                'rooms as available_rooms_count' => fn ($q) => $q->where('status', 'tersedia'),
                'rooms as occupied_rooms_count' => fn ($q) => $q->where('status', 'terisi'),
            ])
            ->get();

        if ($kosList->isEmpty()) {
            return json_encode(['results' => [], 'message' => 'Anda belum memiliki kos terdaftar.'], JSON_UNESCAPED_UNICODE);
        }

        $results = $kosList->map(fn ($k) => [
            'nama' => $k->name,
            'status' => $k->status,
            'total_kamar' => $k->rooms_count,
            'kamar_tersedia' => $k->available_rooms_count,
            'kamar_terisi' => $k->occupied_rooms_count,
        ])->toArray();

        return json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
    }

    protected function toolMyWallet(?User $sender): string
    {
        if (! $sender) {
            return json_encode(['error' => 'Anda harus terdaftar sebagai pemilik kos.'], JSON_UNESCAPED_UNICODE);
        }

        $wallet = AdminWallet::where('admin_id', $sender->id)->first();

        if (! $wallet) {
            return json_encode(['message' => 'Wallet belum tersedia.'], JSON_UNESCAPED_UNICODE);
        }

        return json_encode([
            'saldo_tersedia' => 'Rp'.number_format($wallet->available_balance, 0, ',', '.'),
            'saldo_pending_withdrawal' => 'Rp'.number_format($wallet->pending_withdrawal_balance, 0, ',', '.'),
        ], JSON_UNESCAPED_UNICODE);
    }

    protected function toolMyTenants(?User $sender): string
    {
        if (! $sender) {
            return json_encode(['error' => 'Anda harus terdaftar sebagai pemilik kos.'], JSON_UNESCAPED_UNICODE);
        }

        $tenancies = Tenancy::where('admin_id', $sender->id)
            ->where('status', 'aktif')
            ->with(['user', 'boardingHouse', 'room'])
            ->get();

        if ($tenancies->isEmpty()) {
            return json_encode(['results' => [], 'message' => 'Belum ada penyewa aktif.'], JSON_UNESCAPED_UNICODE);
        }

        $results = $tenancies->map(fn ($t) => [
            'penyewa' => $t->user?->name ?? '-',
            'kos' => $t->boardingHouse?->name ?? '-',
            'kamar' => $t->room?->room_number ?? '-',
            'mulai_sewa' => $t->start_date->format('d/m/Y'),
        ])->toArray();

        return json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
    }

    protected function toolMyReviews(?User $sender): string
    {
        if (! $sender) {
            return json_encode(['error' => 'Anda harus terdaftar sebagai pemilik kos.'], JSON_UNESCAPED_UNICODE);
        }

        $kosIds = BoardingHouse::where('admin_id', $sender->id)->pluck('id');
        $reviews = BoardingHouseReview::whereIn('boarding_house_id', $kosIds)
            ->with(['boardingHouse', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        if ($reviews->isEmpty()) {
            return json_encode(['results' => [], 'message' => 'Belum ada review.'], JSON_UNESCAPED_UNICODE);
        }

        $avgRating = BoardingHouseReview::whereIn('boarding_house_id', $kosIds)->avg('rating');

        $results = $reviews->map(fn ($r) => [
            'kos' => $r->boardingHouse?->name ?? '-',
            'penyewa' => $r->user?->name ?? '-',
            'rating' => $r->rating,
            'komentar' => $r->comment,
        ])->toArray();

        return json_encode([
            'rata_rata_rating' => round($avgRating, 1),
            'total_review' => BoardingHouseReview::whereIn('boarding_house_id', $kosIds)->count(),
            'reviews' => $results,
        ], JSON_UNESCAPED_UNICODE);
    }

    protected function toolPlatformFees(): string
    {
        return json_encode([
            'ppn' => [
                'keterangan' => 'PPN (Pajak Pertambahan Nilai) dikenakan pada tagihan sewa.',
                'persentase' => '11%',
                'ditanggung_oleh' => 'Penyewa',
            ],
            'pph' => [
                'keterangan' => 'PPh (Pajak Penghasilan) dikenakan saat pemilik kos melakukan withdrawal.',
                'persentase' => '10%',
                'ditanggung_oleh' => 'Pemilik Kos',
            ],
            'biaya_platform' => 'Tidak ada biaya platform tambahan saat ini.',
        ], JSON_UNESCAPED_UNICODE);
    }
}
