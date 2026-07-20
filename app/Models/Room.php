<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::deleting(function ($room) {
            $room->tenancies()->delete();
        });
    }
    protected $fillable = [
        'boarding_house_id', 'name', 'room_number', 'description', 'price',
        'price_period', 'capacity', 'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'capacity' => 'integer',
    ];

    public function boardingHouse()
    {
        return $this->belongsTo(BoardingHouse::class);
    }

    public function photos()
    {
        return $this->hasMany(RoomPhoto::class);
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class, 'room_facility')->withTimestamps();
    }

    public function tenancies()
    {
        return $this->hasMany(Tenancy::class);
    }
}
