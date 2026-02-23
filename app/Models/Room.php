<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'room_number',
        'room_type_id',
        'floor',
        'wing',
        'status',
        'housekeeping_status',
        'last_cleaned_at',
        'notes'
    ];

    protected $casts = [
        'last_cleaned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function currentBooking()
    {
        return $this->hasOne(Booking::class)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->latest('check_in_date');
    }

    public function scopeAvailable($query, $checkIn, $checkOut)
    {
        return $query->whereDoesntHave('bookings', function($q) use ($checkIn, $checkOut) {
            $q->whereIn('status', ['confirmed', 'checked_in', 'in_house'])
              ->where(function($query) use ($checkIn, $checkOut) {
                  $query->whereBetween('check_in_date', [$checkIn, $checkOut])
                      ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                      ->orWhere(function($q) use ($checkIn, $checkOut) {
                          $q->where('check_in_date', '<=', $checkIn)
                            ->where('check_out_date', '>=', $checkOut);
                      });
              });
        })->where('status', 'available');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeClean($query)
    {
        return $query->where('housekeeping_status', 'clean');
    }

    public function markAsCleaned()
    {
        $this->update([
            'housekeeping_status' => 'clean',
            'last_cleaned_at' => now()
        ]);
    }
}