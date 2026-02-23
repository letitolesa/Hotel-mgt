<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'menu_item_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_rate_id',
        'special_requests',
        'status',
        'preparation_started_at',
        'preparation_completed_at',
        'served_at'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'preparation_started_at' => 'datetime',
        'preparation_completed_at' => 'datetime',
        'served_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function startPreparation()
    {
        $this->update([
            'status' => 'preparing',
            'preparation_started_at' => now()
        ]);
    }

    public function completePreparation()
    {
        $this->update([
            'status' => 'ready',
            'preparation_completed_at' => now()
        ]);
    }

    public function serve()
    {
        $this->update([
            'status' => 'served',
            'served_at' => now()
        ]);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }
}