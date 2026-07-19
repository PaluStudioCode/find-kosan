<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoardingHouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id', 'name', 'description', 'address', 'public_contact_name',
        'public_contact_whatsapp_number', 'payment_instructions', 'payment_qris_image_path',
        'payment_proof_required', 'city', 'district', 'subdistrict', 'latitude', 'longitude',
        'status', 'verified_at', 'verified_by', 'verification_note', 'pending_revisions',
    ];

    protected $casts = [
        'payment_proof_required' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'verified_at' => 'datetime',
        'pending_revisions' => 'array',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class, 'boarding_house_facility')->withTimestamps();
    }

    public function photos()
    {
        return $this->hasMany(BoardingHousePhoto::class);
    }

    public function legalDocuments()
    {
        return $this->hasMany(BoardingHouseLegalDocument::class);
    }

    public function tenancies()
    {
        return $this->hasMany(Tenancy::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
