<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelTable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hotel_tables';

    protected $fillable = [
        'table_number',
        'table_name',
        'capacity',
        'minimum_capacity',
        'section_id',
        'shape',
        'position_x',
        'position_y',
        'width',
        'height',
        'is_accessible',
        'is_private',
        'has_view',
        'status',
        'cleaning_status',
        'last_cleaned_at',
        'notes'
    ];

    protected $casts = [
        'capacity' => 'integer',
        'minimum_capacity' => 'integer',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'is_accessible' => 'boolean',
        'is_private' => 'boolean',
        'has_view' => 'boolean',
        'last_cleaned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function reservations()
    {
        return $this->hasMany(TableReservation::class);
    }

    public function currentReservation()
    {
        return $this->hasOne(TableReservation::class)
            ->whereIn('status', ['confirmed', 'seated'])
            ->where('reservation_date', now()->toDateString())
            ->latest('reservation_time');
    }

    public function orders()
    {
        return $this->hasMany(TableOrder::class);
    }

    public function waitingList()
    {
        return $this->hasMany(WaitingList::class, 'requested_table_id');
    }

    public function seatedWaitingList()
    {
        return $this->hasMany(WaitingList::class, 'seated_table_id');
    }

    public function availabilityExceptions()
    {
        return $this->hasMany(TableAvailabilityException::class);
    }

    public function scopeAvailable($query, $date, $time, $partySize)
    {
        return $query->where('capacity', '>=', $partySize)
            ->where('status', 'available')
            ->whereDoesntHave('reservations', function($q) use ($date, $time) {
                $q->where('reservation_date', $date)
                  ->where('status', '!=', 'cancelled')
                  ->where('reservation_time', '<=', $time)
                  ->where('end_time', '>=', $time);
            })
            ->whereDoesntHave('availabilityExceptions', function($q) use ($date, $time) {
                $q->where('exception_date', $date)
                  ->where('start_time', '<=', $time)
                  ->where('end_time', '>=', $time);
            });
    }

    public function scopeBySection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeByCapacity($query, $partySize)
    {
        return $query->where('capacity', '>=', $partySize);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function occupy()
    {
        $this->update(['status' => 'occupied']);
    }

    public function release()
    {
        $this->update(['status' => 'available']);
    }

    public function markAsCleaned()
    {
        $this->update([
            'cleaning_status' => 'clean',
            'last_cleaned_at' => now()
        ]);
    }
}