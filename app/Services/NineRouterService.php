<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class NineRouterService
 *
 * Client untuk 9Router (LLM gateway OpenAI-compatible) yang berjalan di localhost:20128.
 * Digunakan oleh WhatsApp Bot untuk:
 *  - Mengambil daftar model (filter Gemini)
 *  - Chat completion biasa (stream:false)
 *  - Function calling multi-turn (tools + tool_calls loop)
 *
 * Default model: gemini-3.1-pro-high (verified working di Fase 0, support tools:true).
 *
 * @see \App\Http\Controllers\WhatsappBotController
 */
class NineRouterService
{
    protected string $baseUrl;

    protected ?string $apiKey;

    protected string $defaultModel;

    /** Timeout untuk request LLM (detik). Model thinking butuh waktu lama. */
    protected int $timeout = 120;

    /** Max token untuk completion (model thinking membutuhkan token besar untuk reasoning). */
    protected int $maxTokens = 2000;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.nine_router.base_url', 'http://localhost:20128'), '/');
        $this->apiKey = config('services.nine_router.api_key');
        $this->defaultModel = config('services.nine_router.default_model', 'gemini-3.1-pro-high');
    }

    /**
     * Ambil daftar semua model dari endpoint 9Router.
     * Format OpenAI: { data: [{ id: "model-name", ... }, ...] }
     *
     * @return array<string> List of model IDs.
     */
    public function listModels(): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->get($this->baseUrl . '/v1/models');

            if (! $response->successful()) {
                Log::warning('[NineRouter] listModels failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();
            $models = collect($data['data'] ?? [])
                ->pluck('id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            return $models;
        } catch (\Exception $e) {
            Log::error('[NineRouter] listModels exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil daftar model yang mengandung kata "gemini" (case-insensitive).
     *
     * @return array<string>
     */
    public function listGeminiModels(): array
    {
        return collect($this->listModels())
            ->filter(fn ($id) => stripos($id, 'gemini') !== false)
            ->values()
            ->all();
    }

    /**
     * Ambil model Gemini default yang akan dipakai bot.
     * Jika setting nine_router_model di-override, pakai itu.
     * Jika tidak, pakai config default (gemini-3.1-pro-high).
     *
     * @return string
     */
    public function getDefaultGeminiModel(): string
    {
        $override = \App\Models\Setting::getSetting('nine_router_model');
        return $override ?: $this->defaultModel;
    }

    /**
     * Chat completion sederhana (tanpa function calling).
     * Untuk kasus yang tidak butuh tool, atau command sederhana.
     *
     * @param array $messages Array of {role, content} (OpenAI format).
     * @param string|null $model Override model default.
     * @return array{content: string, tokens: int, model: string, finish_reason: string}
     *
     * @throws \App\Services\NineRouterException Jika request gagal.
     */
    public function chat(array $messages, ?string $model = null): array
    {
        $payload = [
            'model' => $model ?? $this->getDefaultGeminiModel(),
            'messages' => $messages,
            'stream' => false,
            'max_tokens' => $this->maxTokens,
            'temperature' => 0.3,
        ];

        $response = $this->sendRequest('/v1/chat/completions', $payload);

        $choice = $response['choices'][0] ?? null;
        if (! $choice) {
            throw new NineRouterException('Empty choices in response');
        }

        return [
            'content' => $choice['message']['content'] ?? '',
            'tokens' => $response['usage']['total_tokens'] ?? 0,
            'model' => $response['model'] ?? $payload['model'],
            'finish_reason' => $choice['finish_reason'] ?? 'stop',
        ];
    }

    /**
     * Chat completion dengan function calling support.
     *
     * Method ini mengembalikan response LLM mentah yang mungkin berisi:
     *  - 'content': balasan teks final, ATAU
     *  - 'tool_calls': daftar tool yang harus dipanggil
     *
     * Pemanggil (WhatsappBotController) bertanggung jawab untuk:
     *  1. Deteksi apakah response berisi tool_calls
     *  2. Eksekusi tool yang diminta
     *  3. Tambahkan hasil tool ke messages array
     *  4. Panggil method ini lagi untuk dapat balasan final
     *
     * @param array $messages Array of messages (OpenAI format, termasuk role:tool untuk hasil tool).
     * @param array $tools Array of tool definitions (OpenAI tools format).
     * @param string|null $model Override model default.
     * @return array{
     *     content: string|null,
     *     tool_calls: array|null,
     *     tokens: int,
     *     model: string,
     *     finish_reason: string,
     *     raw_response: array
     * }
     *
     * @throws \App\Services\NineRouterException
     */
    public function chatWithTools(array $messages, array $tools, ?string $model = null): array
    {
        $payload = [
            'model' => $model ?? $this->getDefaultGeminiModel(),
            'messages' => $messages,
            'stream' => false,
            'max_tokens' => $this->maxTokens,
            'temperature' => 0.3,
        ];

        if (! empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = $this->sendRequest('/v1/chat/completions', $payload);

        $choice = $response['choices'][0] ?? null;
        if (! $choice) {
            throw new NineRouterException('Empty choices in response');
        }

        $message = $choice['message'] ?? [];

        return [
            'content' => $message['content'] ?? null,
            'tool_calls' => $message['tool_calls'] ?? null,
            'tokens' => $response['usage']['total_tokens'] ?? 0,
            'model' => $response['model'] ?? $payload['model'],
            'finish_reason' => $choice['finish_reason'] ?? 'stop',
            'raw_response' => $response,
        ];
    }

    /**
     * Kirim HTTP request ke endpoint 9Router.
     *
     * @throws \App\Services\NineRouterException
     */
    protected function sendRequest(string $endpoint, array $payload): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post($this->baseUrl . $endpoint, $payload);

            if (! $response->successful()) {
                Log::error('[NineRouter] Request failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new NineRouterException(
                    '9Router request failed: HTTP ' . $response->status() . ' - ' . $response->body()
                );
            }

            return $response->json() ?? [];
        } catch (NineRouterException $e) {
            throw $e;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('[NineRouter] Connection timeout: ' . $e->getMessage());
            throw new NineRouterException('9Router timeout: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('[NineRouter] Exception: ' . $e->getMessage());
            throw new NineRouterException('9Router error: ' . $e->getMessage());
        }
    }
}
