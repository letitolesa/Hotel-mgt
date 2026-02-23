<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'order_id',
        'customer_id',
        'issued_at',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'promotion_id',
        'total_amount',
        'amount_paid',
        'status',
        'pdf_path',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('issued_at', [$startDate, $endDate]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'issued')
            ->where('due_date', '<', now())
            ->where('amount_due', '>', 0);
    }

    public function addPayment($amount, $method, $userId)
    {
        $payment = $this->order->addPayment($amount, $method, $userId);
        
        $this->amount_paid += $amount;
        $this->status = $this->amount_paid >= $this->total_amount ? 'paid' : 'partially_paid';
        $this->save();

        return $payment;
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'amount_paid' => $this->total_amount
        ]);
    }
}