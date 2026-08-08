<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class WaConversation extends Model
{
    use MassPrunable;

    protected $fillable = [
        'phone_number',
        'role',
        'content',
    ];

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(7));
    }
}
