<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'reviewer_id',
        'reviewer_is_employee',
        'external_reviewer_id',
        'review_period_start',
        'review_period_end',
        'review_date',
        'rating',
        'comments',
        'goals',
        'achievements',
        'areas_for_improvement',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'review_period_start' => 'date',
        'review_period_end' => 'date',
        'review_date' => 'date',
        'rating' => 'integer',
        'reviewer_is_employee' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer()
    {
        if ($this->reviewer_is_employee) {
            return $this->belongsTo(Employee::class, 'reviewer_id');
        }
        return null;
    }

    public function externalReviewer()
    {
        return $this->belongsTo(ExternalReviewer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->where('review_period_start', '>=', $startDate)
            ->where('review_period_end', '<=', $endDate);
    }
}