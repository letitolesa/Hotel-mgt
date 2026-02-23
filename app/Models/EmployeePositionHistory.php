<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePositionHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_position_history';

    protected $fillable = [
        'employee_id',
        'position_id',
        'department_id',
        'effective_date',
        'end_date',
        'base_salary',
        'reason',
        'created_by'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'base_salary' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCurrent($query)
    {
        return $query->whereNull('end_date')
            ->orWhere('end_date', '>=', now());
    }

    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_date', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            });
    }
}