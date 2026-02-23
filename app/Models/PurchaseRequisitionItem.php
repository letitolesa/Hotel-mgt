<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequisitionItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_requisition_items';

    protected $fillable = [
        'purchase_requisition_id',
        'inventory_item_id',
        'quantity_requested',
        'estimated_unit_price',
        'notes'
    ];

    protected $casts = [
        'quantity_requested' => 'integer',
        'estimated_unit_price' => 'decimal:2',
        'estimated_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function purchaseRequisition()
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}