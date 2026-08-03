<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::deleting(function ($invoice) {
            $invoice->payments()->delete();
        });
    }

    protected $fillable = [
        'tenancy_id', 'user_id', 'admin_id', 'period_start', 'period_end',
        'rent_price', 'ppn_percent', 'ppn_amount',
        'amount', 'due_date', 'status', 'payment_reference', 'payment_method', 'payment_url',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'rent_price' => 'decimal:2',
        'ppn_percent' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function tenancy()
    {
        return $this->belongsTo(Tenancy::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function whatsappNotifications()
    {
        return $this->hasMany(WhatsappNotification::class);
    }
}
