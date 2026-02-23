<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'item_type',
        'item_name',
        'amount',
        'description'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function scopeEarnings($query)
    {
        return $query->where('item_type', 'earning');
    }

    public function scopeDeductions($query)
    {
        return $query->where('item_type', 'deduction');
    }
}