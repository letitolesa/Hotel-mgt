<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_reference',
        'customer_id',
        'room_id',
        'rate_plan_id',
        'check_in_date',
        'check_out_date',
        'adults',
        'children',
        'infants',
        'room_rate',
        'extra_charges',
        'discount_amount',
        'tax_amount',
        'total_price',
        'amount_paid',
        'status',
        'booking_source',
        'special_requests',
        'cancellation_reason',
        'cancelled_at',
        'checked_in_at',
        'checked_out_at',
        'created_by'
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'adults' => 'integer',
        'children' => 'integer',
        'infants' => 'integer',
        'number_of_nights' => 'integer',
        'room_rate' => 'decimal:2',
        'extra_charges' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function ratePlan()
    {
        return $this->belongsTo(RatePlan::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function charges()
    {
        return $this->hasMany(BookingCharge::class);
    }

    public function payments()
    {
        return $this->hasMany(BookingPayment::class);
    }

    public function tableReservations()
    {
        return $this->hasMany(TableReservation::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['confirmed', 'checked_in', 'in_house']);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>=', $date);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('check_in_date', [$startDate, $endDate])
            ->orWhereBetween('check_out_date', [$startDate, $endDate]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function checkIn()
    {
        $this->update([
            'status' => 'checked_in',
            'checked_in_at' => now()
        ]);
    }

    public function checkOut()
    {
        $this->update([
            'status' => 'checked_out',
            'checked_out_at' => now()
        ]);
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason
        ]);
    }

    public function addPayment($amount, $method, $userId)
    {
        $payment = $this->payments()->create([
            'amount' => $amount,
            'method' => $method,
            'status' => 'completed',
            'received_by' => $userId,
            'payment_date' => now()
        ]);

        $this->increment('amount_paid', $amount);
        
        if ($this->amount_paid >= $this->total_price) {
            $this->update(['status' => 'confirmed']);
        }

        return $payment;
    }
}