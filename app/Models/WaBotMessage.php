<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaBotMessage extends Model
{
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

    protected function casts(): array
    {
        return [
            'tool_calls' => 'array',
            'tokens_used' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WaBotConversation::class, 'conversation_id');
    }

    public function toOpenAiFormat(): array
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
