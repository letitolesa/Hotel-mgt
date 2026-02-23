<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationRecipient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'notification_recipients';

    protected $fillable = [
        'notification_id',
        'user_id',
        'in_app_status',
        'in_app_sent_at',
        'read_at',
        'email_status',
        'email_sent_at',
        'email_failed_reason'
    ];

    protected $casts = [
        'in_app_sent_at' => 'datetime',
        'read_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead()
    {
        $this->update([
            'in_app_status' => 'read',
            'read_at' => now()
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('in_app_status', 'sent');
    }

    public function scopeRead($query)
    {
        return $query->where('in_app_status', 'read');
    }
}