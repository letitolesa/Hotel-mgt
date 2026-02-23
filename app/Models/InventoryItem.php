<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory_items';

    protected $fillable = [
        'code',
        'name',
        'description',
        'category_id',
        'department_id',
        'unit_of_measure',
        'quantity',
        'minimum_quantity',
        'maximum_quantity',
        'reorder_point',
        'unit_cost',
        'status',
        'notes',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'minimum_quantity' => 'decimal:2',
        'maximum_quantity' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function menuItemIngredients()
    {
        return $this->hasMany(MenuItemIngredient::class);
    }

    public function purchaseRequisitionItems()
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('status', 'in_stock');
    }

    public function scopeLowStock($query)
    {
        return $query->where('status', 'low_stock');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('status', 'out_of_stock');
    }

    public function scopeNeedReorder($query)
    {
        return $query->whereColumn('quantity', '<=', 'reorder_point');
    }

    public function updateStatus()
    {
        if ($this->quantity <= 0) {
            $this->status = 'out_of_stock';
        } elseif ($this->reorder_point && $this->quantity <= $this->reorder_point) {
            $this->status = 'low_stock';
        } else {
            $this->status = 'in_stock';
        }
        
        $this->saveQuietly();
        
        return $this;
    }
}