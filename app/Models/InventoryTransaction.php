<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inventory_item_id',
        'transaction_type',
        'quantity',
        'unit_price',
        'total_price',
        'from_department_id',
        'to_department_id',
        'employee_id',
        'transaction_by',
        'transaction_date',
        'reference_document',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'transaction_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function fromDepartment()
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function transactionBy()
    {
        return $this->belongsTo(User::class, 'transaction_by');
    }

    public function scopeReceipts($query)
    {
        return $query->where('transaction_type', 'receipt');
    }

    public function scopeIssues($query)
    {
        return $query->where('transaction_type', 'issue');
    }

    public function scopeForItem($query, $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('to_department_id', $departmentId)
            ->orWhere('from_department_id', $departmentId);
    }
}