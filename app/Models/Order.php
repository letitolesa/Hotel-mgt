<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'table_id',
        'customer_id',
        'waiter_id',
        'order_type',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'service_charge',
        'total_amount',
        'notes',
        'ordered_at'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function table()
    {
        return $this->belongsTo(HotelTable::class, 'table_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('ordered_at', $date);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('ordered_at', [$startDate, $endDate]);
    }

    public function addItem($data)
    {
        $item = $this->items()->create($data);
        $this->recalculateTotals();
        return $item;
    }

    public function recalculateTotals()
    {
        $this->subtotal = $this->items()->sum('subtotal');
        $this->tax_amount = $this->items()->sum('tax_amount');
        $this->total_amount = $this->subtotal + $this->tax_amount + $this->service_charge - $this->discount_amount;
        $this->save();
    }

    public function applyPromotion($promotion)
    {
        if ($promotion->isValidForOrder($this)) {
            $discount = $promotion->calculateDiscount($this->subtotal);
            $this->discount_amount = $discount;
            $this->recalculateTotals();
            return true;
        }
        return false;
    }

    public function addPayment($amount, $method, $userId)
    {
        $payment = $this->payments()->create([
            'payment_number' => 'PAY-' . uniqid(),
            'amount' => $amount,
            'method' => $method,
            'status' => 'completed',
            'processed_by' => $userId,
            'processed_at' => now()
        ]);

        if ($this->getTotalPaidAttribute() >= $this->total_amount) {
            $this->update(['status' => 'paid']);
        }

        return $payment;
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getBalanceDueAttribute()
    {
        return $this->total_amount - $this->total_paid;
    }
}