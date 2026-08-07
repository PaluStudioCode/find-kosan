<?php

namespace App\Services;

use App\Models\User;
use App\Models\WaBotConversation;
use App\Models\WaBotMessage;
use Illuminate\Support\Facades\Log;

/**
 * Class WhatsappBotReplyService
 *
 * Menangani reply loop end-to-end:
 *  1. Build messages array (system prompt + 10 riwayat pesan + pesan user baru)
 *  2. Call LLM dengan function calling (tools)
 *  3. Jika LLM minta tool call -> eksekusi tool -> kirim hasil ke LLM -> ulang
 *  4. Jika LLM beri teks final -> simpan & return sebagai reply
 *  5. Handle error/timeout -> fallback message
 *
 * Max 5 iterasi tool call untuk mencegah infinite loop.
 */
class WhatsappBotReplyService
{
    /** Max iterasi function calling (mencegah infinite loop). */
    protected const MAX_TOOL_ITERATIONS = 5;

    public function __construct(
        protected NineRouterService $nineRouter,
        protected WhatsappBotContextService $contextService,
    ) {}

    /**
     * Proses pesan user & dapatkan balasan dari LLM.
     *
     * @param  User|null  $sender  User pengirim (null jika public)
     * @param  string  $role  'user' | 'admin' | 'public'
     * @param  string  $userText  Pesan user yang baru saja disimpan
     * @return string Balasan bot untuk dikirim via WA
     */
    public function generateReply(WaBotConversation $conversation, ?User $sender, string $role, string $userText): string
    {
        try {
            // 1. Build system prompt (dengan context_summary dari kompresi riwayat)
            $systemPrompt = $this->contextService->buildSystemPrompt($sender, $role, $conversation->context_summary);

            // 2. Build messages array untuk LLM (system + riwayat terakhir)
            //    Catatan: pesan user baru sudah disimpan sebelumnya, jadi termasuk di recentMessages
            //    Tool results lama diringkas agar LLM tidak menyalin/mendaur ulang data dari pencarian sebelumnya
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            $recent = $conversation->recentMessages((int) config('services.wa_bot.history_size', 10));

            $lastUserMsgIndex = null;
            foreach ($recent as $i => $msg) {
                if ($msg->role === 'user') {
                    $lastUserMsgIndex = $i;
                }
            }

            foreach ($recent as $i => $msg) {
                if ($msg->role === 'system') {
                    continue;
                }

                $isBeforeLastUserMsg = $lastUserMsgIndex !== null && $i < $lastUserMsgIndex;

                if ($isBeforeLastUserMsg && $msg->role === 'tool') {
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $msg->tool_call_id,
                        'content' => json_encode([
                            'summary' => 'Hasil pencarian sebelumnya sudah ditampilkan ke user. Data ini TIDAK BOLEH digunakan lagi. Untuk pertanyaan baru, WAJIB panggil tool lagi dengan keyword baru.',
                        ], JSON_UNESCAPED_UNICODE),
                    ];
                } elseif ($isBeforeLastUserMsg && $msg->role === 'assistant' && ! empty($msg->tool_calls)) {
                    $messages[] = $msg->toOpenAiMessage();
                } elseif ($isBeforeLastUserMsg && $msg->role === 'assistant' && empty($msg->tool_calls)) {
                    $messages[] = [
                        'role' => 'assistant',
                        'content' => '[Jawaban sebelumnya sudah dikirim ke user. Fokus pada pertanyaan terbaru.]',
                    ];
                } else {
                    $messages[] = $msg->toOpenAiMessage();
                }
            }

            // 3. Definisi tools sesuai role
            $tools = $this->contextService->getToolsForRole($role);

            // 4. Loop function calling (max 5 iterasi)
            $iteration = 0;
            while ($iteration < self::MAX_TOOL_ITERATIONS) {
                $iteration++;
                Log::info('[WA Bot] LLM call', [
                    'conversation_id' => $conversation->id,
                    'iteration' => $iteration,
                    'messages_count' => count($messages),
                    'has_tools' => ! empty($tools),
                ]);

                $response = $this->nineRouter->chatWithTools($messages, $tools);

                // Catat token usage (simpan di pesan assistant nanti)
                $tokensUsed = $response['tokens'];
                $modelUsed = $response['model'];

                // Case A: LLM minta tool call
                if (! empty($response['tool_calls'])) {
                    // Simpan pesan assistant dengan tool_calls ke DB & ke messages array
                    $assistantMsg = WaBotMessage::create([
                        'conversation_id' => $conversation->id,
                        'role' => 'assistant',
                        'content' => $response['content'],
                        'tool_calls' => $response['tool_calls'],
                        'tokens_used' => $tokensUsed,
                        'model_used' => $modelUsed,
                    ]);
                    $messages[] = $assistantMsg->toOpenAiMessage();

                    // Eksekusi setiap tool call & tambahkan hasil sebagai role:tool
                    foreach ($response['tool_calls'] as $toolCall) {
                        $toolName = $toolCall['function']['name'] ?? '';
                        $arguments = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];
                        $toolCallId = $toolCall['id'] ?? '';

                        Log::info('[WA Bot] Tool call', [
                            'tool' => $toolName,
                            'arguments' => $arguments,
                            'conversation_id' => $conversation->id,
                        ]);

                        $toolResult = $this->contextService->executeTool($toolName, $arguments, $sender);

                        // Simpan hasil tool ke DB
                        WaBotMessage::create([
                            'conversation_id' => $conversation->id,
                            'role' => 'tool',
                            'content' => $toolResult,
                            'tool_call_id' => $toolCallId,
                            'tool_name' => $toolName,
                            'tokens_used' => 0,
                            'model_used' => null,
                        ]);

                        // Tambahkan ke messages array untuk LLM
                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCallId,
                            'content' => $toolResult,
                        ];
                    }

                    // Lanjut iterasi untuk LLM proses hasil tool
                    continue;
                }

                // Case B: LLM beri balasan teks final
                $finalReply = $response['content'] ?? '';
                if (trim($finalReply) === '') {
                    $finalReply = 'Maaf, saya tidak bisa memproses permintaan Anda saat ini. Silakan coba lagi.';
                }

                // Simpan pesan assistant final ke DB
                WaBotMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $finalReply,
                    'tokens_used' => $tokensUsed,
                    'model_used' => $modelUsed,
                ]);
                $conversation->touchActivity();

                Log::info('[WA Bot] Reply generated', [
                    'conversation_id' => $conversation->id,
                    'iterations' => $iteration,
                    'tokens' => $tokensUsed,
                    'reply_length' => strlen($finalReply),
                ]);

                return $finalReply;
            }

            // Jika sampai sini, berarti hit max iterations tanpa final reply
            Log::warning('[WA Bot] Max tool iterations reached', [
                'conversation_id' => $conversation->id,
            ]);

            return 'Maaf, permintaan Anda terlalu kompleks untuk diproses. Mohon sederhanakan pertanyaan Anda.';
        } catch (NineRouterException $e) {
            Log::error('[WA Bot] LLM error', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return 'Maaf, bot sedang mengalami gangguan. Silakan coba lagi sebentar ya. 😊';
        } catch (\Exception $e) {
            Log::error('[WA Bot] Reply generation failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return 'Maaf, terjadi kesalahan. Silakan coba lagi nanti.';
        }
    }
}
