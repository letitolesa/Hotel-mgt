<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'membership_number',
        'loyalty_points',
        'loyalty_tier',
        'preferences',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'date_of_birth',
        'identification_type',
        'identification_number',
        'company',
        'tax_id'
    ];

    protected $casts = [
        'loyalty_points' => 'integer',
        'preferences' => 'array',
        'date_of_birth' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function tableReservations()
    {
        return $this->hasMany(TableReservation::class);
    }

    public function waitingList()
    {
        return $this->hasMany(WaitingList::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function scopeByTier($query, $tier)
    {
        return $query->where('loyalty_tier', $tier);
    }

    public function scopeWithMembership($query)
    {
        return $query->whereNotNull('membership_number');
    }

    public function addLoyaltyPoints($points)
    {
        $this->increment('loyalty_points', $points);
        $this->updateTier();
    }

    public function redeemLoyaltyPoints($points)
    {
        if ($this->loyalty_points >= $points) {
            $this->decrement('loyalty_points', $points);
            return true;
        }
        return false;
    }

    protected function updateTier()
    {
        $tier = match(true) {
            $this->loyalty_points >= 10000 => 'platinum',
            $this->loyalty_points >= 5000 => 'gold',
            $this->loyalty_points >= 1000 => 'silver',
            default => 'bronze'
        };
        
        $this->update(['loyalty_tier' => $tier]);
    }
}