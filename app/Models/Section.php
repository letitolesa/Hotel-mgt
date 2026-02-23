<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'location',
        'is_smoking',
        'is_outdoor',
        'is_private',
        'min_capacity',
        'max_capacity',
        'image_url',
        'is_active'
    ];

    protected $casts = [
        'is_smoking' => 'boolean',
        'is_outdoor' => 'boolean',
        'is_private' => 'boolean',
        'min_capacity' => 'integer',
        'max_capacity' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function tables()
    {
        return $this->hasMany(HotelTable::class);
    }

    public function waiters()
    {
        return $this->hasMany(Waiter::class);
    }

    public function waitingList()
    {
        return $this->hasMany(WaitingList::class, 'requested_section_id');
    }

    public function availabilityExceptions()
    {
        return $this->hasMany(TableAvailabilityException::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }
}