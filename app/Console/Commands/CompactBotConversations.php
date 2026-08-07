<?php

namespace App\Console\Commands;

use App\Models\WaBotConversation;
use App\Services\NineRouterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CompactBotConversations extends Command
{
    protected $signature = 'whatsapp:compact-conversations';

    protected $description = 'Kompres riwayat percakapan bot WhatsApp yang panjang (>50 pesan)';

    public function handle(NineRouterService $nineRouter): int
    {
        $conversations = WaBotConversation::where('total_messages', '>', 50)->get();

        if ($conversations->isEmpty()) {
            $this->info('Tidak ada percakapan yang perlu dikompres.');

            return self::SUCCESS;
        }

        $this->info("Memproses {$conversations->count()} percakapan...");

        foreach ($conversations as $conversation) {
            try {
                $messages = $conversation->messages()
                    ->whereIn('role', ['user', 'assistant'])
                    ->orderBy('id', 'asc')
                    ->get();

                $keepCount = 10;
                if ($messages->count() <= $keepCount) {
                    continue;
                }

                $toCompact = $messages->slice(0, -$keepCount);
                $compactText = $toCompact->map(fn ($m) => "[{$m->role}]: {$m->content}")->implode("\n");

                $response = $nineRouter->chat([
                    ['role' => 'system', 'content' => 'Rangkum percakapan berikut dalam maksimal 5 kalimat singkat dalam Bahasa Indonesia. Fokus pada topik utama dan informasi penting. Jangan sertakan data spesifik seperti harga atau nama kos.'],
                    ['role' => 'user', 'content' => $compactText],
                ]);

                $summary = $response['content'] ?? '';
                if (empty($summary)) {
                    continue;
                }

                $existingSummary = $conversation->context_summary;
                $newSummary = $existingSummary
                    ? $existingSummary."\n---\n".$summary
                    : $summary;

                $compactIds = $toCompact->pluck('id')->toArray();
                $conversation->messages()->whereIn('id', $compactIds)->delete();

                $toolMsgIds = $conversation->messages()
                    ->where('role', 'tool')
                    ->whereNotIn('id', $conversation->messages()
                        ->where('role', 'assistant')
                        ->whereNotNull('tool_calls')
                        ->pluck('id'))
                    ->pluck('id');

                $conversation->update([
                    'context_summary' => $newSummary,
                    'total_messages' => $conversation->messages()->count(),
                ]);

                $this->info("Conversation #{$conversation->id}: dikompres ({$toCompact->count()} pesan → ringkasan)");
            } catch (\Exception $e) {
                Log::error('[WA Bot Compact] Failed', [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Conversation #{$conversation->id}: gagal - {$e->getMessage()}");
            }
        }

        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
