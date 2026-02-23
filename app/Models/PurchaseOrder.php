<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'purchase_requisition_id',
        'order_date',
        'expected_delivery_date',
        'delivery_address',
        'shipping_method',
        'payment_terms',
        'status',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'shipping_cost',
        'created_by',
        'approved_by',
        'approved_at',
        'notes'
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseRequisition()
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceivedNotes()
    {
        return $this->hasMany(GoodsReceivedNote::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['sent', 'confirmed']);
    }

    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function receiveItems($items, $receivedBy)
    {
        $grn = $this->goodsReceivedNotes()->create([
            'grn_number' => 'GRN-' . uniqid(),
            'received_by' => $receivedBy,
            'received_date' => now(),
            'status' => 'completed'
        ]);

        foreach ($items as $itemData) {
            $poItem = $this->items()->find($itemData['purchase_order_item_id']);
            $poItem->quantity_received += $itemData['quantity_received'];
            $poItem->save();

            $grn->items()->create([
                'purchase_order_item_id' => $poItem->id,
                'quantity_received' => $itemData['quantity_received'],
                'batch_number' => $itemData['batch_number'] ?? null,
                'expiry_date' => $itemData['expiry_date'] ?? null,
                'manufacturing_date' => $itemData['manufacturing_date'] ?? null,
                'condition_notes' => $itemData['condition_notes'] ?? null
            ]);

            // Update inventory
            $poItem->inventoryItem->increment('quantity', $itemData['quantity_received']);
        }

        $this->updateStatus();
        
        return $grn;
    }

    protected function updateStatus()
    {
        $totalOrdered = $this->items->sum('quantity_ordered');
        $totalReceived = $this->items->sum('quantity_received');
        
        if ($totalReceived >= $totalOrdered) {
            $this->status = 'received';
        } elseif ($totalReceived > 0) {
            $this->status = 'partially_received';
        }
        
        $this->save();
    }
}