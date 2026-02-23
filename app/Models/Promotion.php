<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_per_customer',
        'start_date',
        'end_date',
        'days_of_week',
        'applicable_to',
        'applicable_ids',
        'is_active',
        'conditions',
        'created_by'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_per_customer' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'days_of_week' => 'array',
        'applicable_ids' => 'array',
        'is_active' => 'boolean',
        'conditions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function isValidForOrder($order)
    {
        // Check if promotion is active
        if (!$this->is_active || $this->start_date > now() || $this->end_date < now()) {
            return false;
        }

        // Check day of week
        if ($this->days_of_week && !in_array(now()->dayOfWeek, $this->days_of_week)) {
            return false;
        }

        // Check minimum order amount
        if ($this->min_order_amount && $order->subtotal < $this->min_order_amount) {
            return false;
        }

        return true;
    }

    public function calculateDiscount($amount)
    {
        $discount = match($this->type) {
            'percentage' => $amount * ($this->value / 100),
            'fixed_amount' => $this->value,
            default => 0
        };

        if ($this->max_discount_amount) {
            $discount = min($discount, $this->max_discount_amount);
        }

        return $discount;
    }
}