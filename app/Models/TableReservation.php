<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TableReservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'table_reservations';

    protected $fillable = [
        'reservation_number',
        'booking_id',
        'customer_id',
        'table_id',
        'party_size',
        'reservation_date',
        'reservation_time',
        'duration_minutes',
        'status',
        'source',
        'is_hotel_guest',
        'room_number',
        'bill_amount',
        'bill_paid',
        'payment_method',
        'special_requests',
        'occasion',
        'dietary_restrictions',
        'confirmed_by',
        'confirmed_at',
        'seated_at',
        'completed_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'created_by'
    ];

    protected $casts = [
        'party_size' => 'integer',
        'duration_minutes' => 'integer',
        'reservation_date' => 'date',
        'reservation_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'bill_amount' => 'decimal:2',
        'bill_paid' => 'boolean',
        'is_hotel_guest' => 'boolean',
        'confirmed_at' => 'datetime',
        'seated_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function table()
    {
        return $this->belongsTo(HotelTable::class, 'table_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders()
    {
        return $this->hasMany(TableOrder::class, 'table_reservation_id');
    }

    public function history()
    {
        return $this->hasMany(ReservationHistory::class, 'reservation_id');
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('reservation_date', $date);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('reservation_date', [$startDate, $endDate]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTable($query, $tableId)
    {
        return $query->where('table_id', $tableId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('reservation_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public function confirm($userId)
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_by' => $userId,
            'confirmed_at' => now()
        ]);
        
        $this->history()->create([
            'action' => 'confirmed',
            'old_status' => 'pending',
            'new_status' => 'confirmed',
            'performed_by' => $userId
        ]);
    }

    public function seat()
    {
        $this->update([
            'status' => 'seated',
            'seated_at' => now()
        ]);
        
        $this->table->occupy();
        
        $this->history()->create([
            'action' => 'seated',
            'old_status' => $this->status,
            'new_status' => 'seated'
        ]);
    }

    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
        
        $this->table->release();
        
        $this->history()->create([
            'action' => 'completed',
            'old_status' => $this->status,
            'new_status' => 'completed'
        ]);
    }

    public function cancel($reason, $userId)
    {
        $oldStatus = $this->status;
        
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $userId
        ]);
        
        $this->history()->create([
            'action' => 'cancelled',
            'old_status' => $oldStatus,
            'new_status' => 'cancelled',
            'notes' => $reason,
            'performed_by' => $userId
        ]);
    }

    public function addOrder($data)
    {
        $order = $this->orders()->create($data);
        return $order;
    }
}