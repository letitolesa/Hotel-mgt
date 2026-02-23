<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingCharge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'description',
        'amount',
        'charge_type',
        'charge_date',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charge_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}