<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'notification_number',
        'notification_type_id',
        'title',
        'body',
        'data',
        'action_url',
        'email_subject',
        'email_sent',
        'scheduled_for',
        'expires_at',
        'sent_at',
        'status',
        'error_message',
        'created_by'
    ];

    protected $casts = [
        'data' => 'array',
        'email_sent' => 'boolean',
        'scheduled_for' => 'datetime',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function type()
    {
        return $this->belongsTo(NotificationType::class, 'notification_type_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients()
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    public function broadcasts()
    {
        return $this->hasMany(NotificationBroadcast::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'notification_recipients')
            ->withPivot(['in_app_status', 'read_at', 'email_status'])
            ->withTimestamps();
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'queued')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now());
    }

    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('recipients', function($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function scopeUnread($query, $userId)
    {
        return $query->whereHas('recipients', function($q) use ($userId) {
            $q->where('user_id', $userId)
              ->where('in_app_status', 'sent');
        });
    }
}