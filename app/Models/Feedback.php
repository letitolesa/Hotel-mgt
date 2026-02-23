<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'feedback_number',
        'customer_id',
        'booking_id',
        'order_id',
        'table_reservation_id',
        'rating',
        'title',
        'comment',
        'category',
        'is_public',
        'is_anonymous',
        'responded_by',
        'response',
        'responded_at'
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_public' => 'boolean',
        'is_anonymous' => 'boolean',
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function tableReservation()
    {
        return $this->belongsTo(TableReservation::class);
    }

    public function respondedBy()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function respond($response, $userId)
    {
        $this->update([
            'response' => $response,
            'responded_by' => $userId,
            'responded_at' => now()
        ]);
    }
}