<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaBotConversation extends Model
{
    protected $table = 'wa_bot_conversations';

    protected $fillable = [
        'from_jid',
        'phone_number',
        'user_id',
        'identified_role',
        'context_summary',
        'is_bot_enabled',
        'total_messages',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'is_bot_enabled' => 'boolean',
            'total_messages' => 'integer',
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WaBotMessage::class, 'conversation_id')->orderBy('id', 'asc');
    }

    public function recentMessages(int $limit = 20)
    {
        return $this->messages()
            ->whereIn('role', ['user', 'assistant', 'tool'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function touchActivity(): void
    {
        $this->increment('total_messages');
        $this->update(['last_message_at' => now()]);
    }
}
