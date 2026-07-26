<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaSession extends Model
{
    protected $fillable = [
        'owner_id',
        'status',
        'phone_number',
        'connected_at',
        'disconnected_at',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
