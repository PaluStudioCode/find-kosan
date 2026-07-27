<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        return $this->hasMany(BoardingHouse::class, 'admin_id');
    }

    public function tenanciesAsTenant()
    {
        return $this->hasMany(Tenancy::class, 'user_id');
    }

    public function tenanciesAsOwner()
    {
        return $this->hasMany(Tenancy::class, 'admin_id');
    }

    public function wallet()
    {
        return $this->hasOne(AdminWallet::class, 'admin_id');
    }

    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class, 'admin_id');
    }

    public function boardingHouseReviews()
    {
        return $this->hasMany(BoardingHouseReview::class);
    }

    public function waSession()
    {
        return $this->hasOne(WaSession::class, 'admin_id');
    }
}
