<?php

namespace App\Console\Commands;

use App\Models\WaBotConversation;
use App\Models\WaBotMessage;
use App\Services\NineRouterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class CompactBotConversations
 *
 * Kompresi riwayat percakapan bot WhatsApp yang sudah terlalu panjang
 * (> threshold pesan) agar token yang dikirim ke LLM tetap terkontrol.
 *
 * Cara kerja:
 *  1. Ambil semua conversation dengan total_messages > threshold
 *  2. Ambil N pesan terlama yang akan dikompresi
 *  3. Minta LLM rangkum pesan-pesan tsb dalam beberapa kalimat
 *  4. Simpan ringkasan ke context_summary (append jika sudah ada)
 *  5. Hapus pesan-pesan terlama yang sudah dirangkum
 *  6. Reset counter total_messages
 *
 * Run: php artisan whatsapp:compact-bot-conversations
 * Scheduled: weekly (lihat routes/console.php)
 */
class CompactBotConversations extends Command
{
    /** Threshold: conversation dengan pesan lebih dari ini akan dikompresi. */
    const COMPACTION_THRESHOLD = 50;

    /** Jumlah pesan terlama yang akan dirangkum per run. */
    const MESSAGES_TO_COMPACT = 40;

    /** Jumlah pesan yang dipertahankan (tidak dikompresi). */
    const KEEP_RECENT = 10;

    protected $signature = 'whatsapp:compact-bot-conversations';

    protected $description = 'Kompresi riwayat percakapan bot WhatsApp yang panjang (>50 pesan) menggunakan LLM';

    public function handle(NineRouterService $nineRouter): int
    {
        $conversations = WaBotConversation::where('total_messages', '>', self::COMPACTION_THRESHOLD)->get();

        if ($conversations->isEmpty()) {
            $this->info('No conversations need compaction.');
            return self::SUCCESS;
        }

        $this->info("Found {$conversations->count()} conversation(s) to compact.");
        $compacted = 0;

        foreach ($conversations as $conversation) {
            try {
                $totalMessages = $conversation->messages()->count();
                if ($totalMessages <= self::KEEP_RECENT) {
                    continue;
                }

                // Ambil pesan terlama yang akan dikompresi (skip KEEP_RECENT terbaru)
                $toCompact = $conversation->messages()
                    ->orderBy('id')
                    ->limit($totalMessages - self::KEEP_RECENT)
                    ->get();

                if ($toCompact->isEmpty()) {
                    continue;
                }

                // Build teks untuk dirangkum
                $conversationText = $toCompact->map(function ($msg) {
                    $role = $msg->role === 'assistant' ? 'Bot' : ($msg->role === 'user' ? 'User' : 'System');
                    $content = $msg->content ?? '(tool call)';
                    return "[{$role}]: {$content}";
                })->implode("\n");

                // Minta LLM rangkum
                $summary = $this->summarizeConversation($nineRouter, $conversationText, $conversation->context_summary);

                if ($summary === null) {
                    $this->warn("Failed to summarize conversation {$conversation->id}, skipping.");
                    continue;
                }

                // Simpan ringkasan (append ke context_summary yang ada)
                $existingSummary = $conversation->context_summary;
                $conversation->context_summary = $existingSummary
                    ? $existingSummary . "\n\n---\n\n" . $summary
                    : $summary;

                // Hapus pesan yang sudah dikompresi
                $toCompact->each(fn ($msg) => $msg->delete());

                // Update counter
                $conversation->total_messages = $conversation->messages()->count();
                $conversation->save();

                $compacted++;
                $this->info("Compacted conversation #{$conversation->id}: removed " . $toCompact->count() . ' messages, summary saved.');

                Log::info('[WA Bot] Conversation compacted', [
                    'conversation_id' => $conversation->id,
                    'removed_messages' => $toCompact->count(),
                    'summary_length' => strlen($summary),
                ]);
            } catch (\Exception $e) {
                $this->error("Failed to compact conversation {$conversation->id}: {$e->getMessage()}");
                Log::error('[WA Bot] Compaction failed', [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. Compacted {$compacted} conversation(s).");
        return self::SUCCESS;
    }

    /**
     * Minta LLM untuk merangkum percakapan.
     */
    protected function summarizeConversation(NineRouterService $nineRouter, string $conversationText, ?string $existingSummary): ?string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'Anda adalah asisten yang merangkum percakapan WhatsApp bot. Buat ringkasan SINGKAT (maksimal 5 kalimat) berisi poin-poin penting: siapa pengirim, apa yang ditanyakan, dan info kunci yang sudah dibagikan. Ringkasan ini akan dipakai sebagai konteks untuk percakapan selanjutnya.'
                    . ($existingSummary ? ' Ada ringkasan sebelumnya yang harus Anda gabungkan dengan ringkasan baru.' : ''),
            ],
            [
                'role' => 'user',
                'content' => ($existingSummary ? "Ringkasan sebelumnya:\n{$existingSummary}\n\n" : '')
                    . "Percakapan yang perlu dirangkum:\n" . $conversationText,
            ],
        ];

        try {
            $result = $nineRouter->chat($messages);
            return $result['content'] ?: null;
        } catch (\Exception $e) {
            Log::error('[WA Bot] Summary generation failed: ' . $e->getMessage());
            return null;
        }
    }
}
