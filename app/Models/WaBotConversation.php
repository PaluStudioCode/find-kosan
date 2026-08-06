<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaBotConversation extends Model
{
    use HasFactory;

    protected $table = 'wa_bot_conversations';

    protected $fillable = [
        'phone_number',
        'from_jid',
        'user_id',
        'identified_role',
        'context_summary',
        'last_message_at',
        'is_bot_enabled',
        'total_messages',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_bot_enabled' => 'boolean',
        'total_messages' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WaBotMessage::class, 'conversation_id')->orderBy('id', 'asc');
    }

    /**
     * Scope: ambil conversation berdasarkan nomor WA (normalized).
     */
    public function scopeForPhone($query, string $phone)
    {
        return $query->where('phone_number', $phone);
    }

    /**
     * Ambil N pesan terakhir untuk dikirim ke LLM.
     */
    public function recentMessages(int $limit = 10)
    {
        return $this->messages()
            ->whereIn('role', ['system', 'user', 'assistant'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Tambah counter pesan & update last_message_at.
     */
    public function touchActivity(): void
    {
        $this->increment('total_messages');
        $this->update(['last_message_at' => now()]);
    }
}
