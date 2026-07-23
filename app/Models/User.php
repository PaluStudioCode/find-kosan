<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'whatsapp_number',
        'role',
        'status',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function boardingHouses()
    {
        return $this->hasMany(BoardingHouse::class, 'owner_id');
    }

    public function tenanciesAsTenant()
    {
        return $this->hasMany(Tenancy::class, 'tenant_id');
    }

    public function tenanciesAsOwner()
    {
        return $this->hasMany(Tenancy::class, 'owner_id');
    }

    public function wallet()
    {
        return $this->hasOne(OwnerWallet::class, 'owner_id');
    }

    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class, 'owner_id');
    }
}
