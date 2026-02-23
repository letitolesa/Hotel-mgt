<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ulid',
        'code',
        'name',
        'description',
        'category_id',
        'price',
        'cost',
        'is_taxable',
        'preparation_time_minutes',
        'is_available',
        'is_featured',
        'image_path',
        'allergens',
        'nutritional_info',
        'recipe'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_taxable' => 'boolean',
        'preparation_time_minutes' => 'integer',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'nutritional_info' => 'array',
        'recipe' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function ingredients()
    {
        return $this->hasMany(MenuItemIngredient::class);
    }

    public function inventoryItems()
    {
        return $this->belongsToMany(InventoryItem::class, 'menu_item_ingredients')
            ->withPivot(['quantity', 'unit_id', 'wastage_percent'])
            ->withTimestamps();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function getProfitMarginAttribute()
    {
        if ($this->cost) {
            return (($this->price - $this->cost) / $this->price) * 100;
        }
        return null;
    }
}