<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TableOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'table_orders';

    protected $fillable = [
        'order_number',
        'table_reservation_id',
        'table_id',
        'customer_id',
        'booking_id',
        'order_type',
        'status',
        'server_id',
        'order_time',
        'ready_time',
        'served_time',
        'subtotal',
        'tax_amount',
        'service_charge',
        'discount_amount',
        'total_amount',
        'is_paid',
        'payment_method',
        'room_number',
        'delivery_notes',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'order_time' => 'datetime',
        'ready_time' => 'datetime',
        'served_time' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function tableReservation()
    {
        return $this->belongsTo(TableReservation::class);
    }

    public function table()
    {
        return $this->belongsTo(HotelTable::class, 'table_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function server()
    {
        return $this->belongsTo(User::class, 'server_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForTable($query, $tableId)
    {
        return $query->where('table_id', $tableId);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'preparing', 'ready']);
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

    public function markAsReady()
    {
        $this->update([
            'status' => 'ready',
            'ready_time' => now()
        ]);
    }

    public function markAsServed()
    {
        $this->update([
            'status' => 'served',
            'served_time' => now()
        ]);
    }

    public function markAsPaid($method)
    {
        $this->update([
            'status' => 'completed',
            'is_paid' => true,
            'payment_method' => $method
        ]);
    }
}