<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaBotMessage extends Model
{
    use HasFactory;

    protected $table = 'wa_bot_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tool_call_id',
        'tool_name',
        'tool_calls',
        'tokens_used',
        'model_used',
    ];

    protected $casts = [
        'tool_calls' => 'array',
        'tokens_used' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WaBotConversation::class, 'conversation_id');
    }

    /**
     * Konversi ke format message OpenAI-compatible.
     * Digunakan saat membangun array messages untuk request LLM.
     */
    public function toOpenAiMessage(): array
    {
        $msg = ['role' => $this->role];

        if ($this->role === 'tool') {
            $msg['tool_call_id'] = $this->tool_call_id;
            $msg['content'] = $this->content ?? '';
        } elseif ($this->role === 'assistant' && $this->tool_calls) {
            $msg['content'] = $this->content ?: null;
            $msg['tool_calls'] = $this->tool_calls;
        } else {
            $msg['content'] = $this->content ?? '';
        }

        return $msg;
    }
}
