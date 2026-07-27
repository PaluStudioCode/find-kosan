<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaSession extends Model
{
    protected $fillable = [
        'admin_id',
        'status',
        'phone_number',
        'connected_at',
        'disconnected_at',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
