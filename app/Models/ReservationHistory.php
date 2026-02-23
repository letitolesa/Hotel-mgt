<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reservation_history';

    protected $fillable = [
        'reservation_id',
        'action',
        'old_status',
        'new_status',
        'changes',
        'notes',
        'performed_by'
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime'
    ];

    public function reservation()
    {
        return $this->belongsTo(TableReservation::class, 'reservation_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}