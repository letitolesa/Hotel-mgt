<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TableAvailabilityException extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'table_availability_exceptions';

    protected $fillable = [
        'table_id',
        'section_id',
        'exception_date',
        'start_time',
        'end_time',
        'reason',
        'exception_type',
        'is_recurring',
        'recurring_pattern',
        'recurring_end_date',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'exception_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_recurring' => 'boolean',
        'recurring_end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function table()
    {
        return $this->belongsTo(HotelTable::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('exception_date', $date);
    }

    public function scopeForTable($query, $tableId)
    {
        return $query->where('table_id', $tableId);
    }

    public function scopeForSection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeActive($query, $date, $time)
    {
        return $query->where('exception_date', $date)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time);
    }
}