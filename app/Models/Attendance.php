<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'schedule_id',
        'checked_in_at',
        'checked_out_at',
        'check_in_method',
        'check_out_method',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'check_in_photo_path',
        'check_out_photo_path',
        'status',
        'overtime_minutes',
        'late_minutes',
        'early_departure_minutes',
        'notes',
        'verified_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'check_in_latitude' => 'decimal:8',
        'check_in_longitude' => 'decimal:8',
        'check_out_latitude' => 'decimal:8',
        'check_out_longitude' => 'decimal:8',
        'overtime_minutes' => 'integer',
        'late_minutes' => 'integer',
        'early_departure_minutes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('checked_in_at', $date);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('checked_in_at', [$startDate, $endDate]);
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', ['present', 'late', 'early_departure']);
    }

    public function getWorkedHoursAttribute()
    {
        if (!$this->checked_out_at) {
            return null;
        }
        
        return $this->checked_in_at->diffInHours($this->checked_out_at);
    }
}