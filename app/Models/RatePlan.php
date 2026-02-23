<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RatePlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'room_type_id',
        'base_rate',
        'cancellation_policy',
        'prepayment_required',
        'includes_breakfast',
        'is_refundable',
        'min_stay',
        'max_stay',
        'is_active'
    ];

    protected $casts = [
        'base_rate' => 'decimal:2',
        'prepayment_required' => 'boolean',
        'includes_breakfast' => 'boolean',
        'is_refundable' => 'boolean',
        'min_stay' => 'integer',
        'max_stay' => 'integer',
        'is_active' => 'boolean',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRoomType($query, $roomTypeId)
    {
        return $query->where('room_type_id', $roomTypeId);
    }
}