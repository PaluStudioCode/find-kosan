<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomPhoto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'room_id', 'file_path', 'caption', 'sort_order', 'is_primary',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
