<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceivedNoteItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'goods_received_note_items';

    protected $fillable = [
        'goods_received_note_id',
        'purchase_order_item_id',
        'quantity_received',
        'batch_number',
        'expiry_date',
        'manufacturing_date',
        'condition_notes'
    ];

    protected $casts = [
        'quantity_received' => 'integer',
        'expiry_date' => 'date',
        'manufacturing_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function goodsReceivedNote()
    {
        return $this->belongsTo(GoodsReceivedNote::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
}