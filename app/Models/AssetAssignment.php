<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_id',
        'employee_id',
        'department_id',
        'assigned_by',
        'assigned_date',
        'expected_return_date',
        'returned_date',
        'condition_on_return',
        'notes',
        'status'
    ];

    protected $casts = [
        'assigned_date' => 'datetime',
        'expected_return_date' => 'date',
        'returned_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'assigned')
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', now());
    }
}