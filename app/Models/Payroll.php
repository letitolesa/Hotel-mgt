<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payroll_number',
        'employee_id',
        'pay_period_start',
        'pay_period_end',
        'basic_salary',
        'overtime_pay',
        'allowances',
        'bonuses',
        'commission',
        'tax_deduction',
        'social_security_deduction',
        'health_insurance_deduction',
        'other_deductions',
        'payment_method',
        'status',
        'generated_by',
        'approved_by',
        'approved_at',
        'paid_at',
        'payment_reference',
        'notes'
    ];

    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'basic_salary' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'allowances' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'commission' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'tax_deduction' => 'decimal:2',
        'social_security_deduction' => 'decimal:2',
        'health_insurance_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function termination()
    {
        return $this->hasOne(EmployeeTermination::class, 'final_payroll_id');
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('pay_period_start', '>=', $startDate)
            ->where('pay_period_end', '<=', $endDate);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}