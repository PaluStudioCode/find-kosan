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
        'tenancy_id', 'tenant_id', 'owner_id', 'period_start', 'period_end',
        'amount', 'due_date', 'status', 'payment_reference', 'payment_method', 'payment_url',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function tenancy()
    {
        return $this->belongsTo(Tenancy::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
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
