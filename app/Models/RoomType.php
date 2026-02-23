<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'base_price',
        'max_occupancy',
        'size_sq_meters',
        'bed_type',
        'amenities',
        'images',
        'is_active'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'max_occupancy' => 'integer',
        'size_sq_meters' => 'integer',
        'amenities' => 'array',
        'images' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function ratePlans()
    {
        return $this->hasMany(RatePlan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCapacity($query, $occupancy)
    {
        return $query->where('max_occupancy', '>=', $occupancy);
    }
}