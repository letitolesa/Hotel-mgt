<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItemIngredient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'menu_item_ingredients';

    protected $fillable = [
        'menu_item_id',
        'inventory_item_id',
        'quantity',
        'unit_id',
        'wastage_percent'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'wastage_percent' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getEffectiveQuantityAttribute()
    {
        return $this->quantity * (1 + $this->wastage_percent / 100);
    }
}