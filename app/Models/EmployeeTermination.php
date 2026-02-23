<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeTermination extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'termination_date',
        'termination_type',
        'reason',
        'eligible_for_rehire',
        'final_payroll_id',
        'exit_interview_notes',
        'created_by'
    ];

    protected $casts = [
        'termination_date' => 'date',
        'eligible_for_rehire' => 'boolean',
        'created_at' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function finalPayroll()
    {
        return $this->belongsTo(Payroll::class, 'final_payroll_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}