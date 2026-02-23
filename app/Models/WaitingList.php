<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WaitingList extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'waiting_list';

    protected $fillable = [
        'waiting_number',
        'customer_id',
        'booking_id',
        'party_size',
        'requested_section_id',
        'requested_table_id',
        'check_in_time',
        'estimated_wait_minutes',
        'sms_notification',
        'phone_number',
        'sms_sent',
        'notified_time',
        'status',
        'seated_at',
        'seated_table_id',
        'cancelled_reason',
        'is_hotel_guest',
        'room_number',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'party_size' => 'integer',
        'estimated_wait_minutes' => 'integer',
        'check_in_time' => 'datetime',
        'notified_time' => 'datetime',
        'seated_at' => 'datetime',
        'sms_notification' => 'boolean',
        'sms_sent' => 'boolean',
        'is_hotel_guest' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function requestedSection()
    {
        return $this->belongsTo(Section::class, 'requested_section_id');
    }

    public function requestedTable()
    {
        return $this->belongsTo(HotelTable::class, 'requested_table_id');
    }

    public function seatedTable()
    {
        return $this->belongsTo(HotelTable::class, 'seated_table_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeNotified($query)
    {
        return $query->where('status', 'notified');
    }

    public function scopeByPartySize($query, $size)
    {
        return $query->where('party_size', '>=', $size);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('check_in_time', $date);
    }

    public function notify()
    {
        $this->update([
            'status' => 'notified',
            'notified_time' => now(),
            'sms_sent' => true
        ]);
    }

    public function seat($tableId)
    {
        $this->update([
            'status' => 'seated',
            'seated_at' => now(),
            'seated_table_id' => $tableId
        ]);
    }
}