<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleNotificationDefault extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'role_notification_defaults';

    protected $fillable = [
        'role_id',
        'notification_type_id',
        'receive_in_app',
        'receive_email'
    ];

    protected $casts = [
        'receive_in_app' => 'boolean',
        'receive_email' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function notificationType()
    {
        return $this->belongsTo(NotificationType::class);
    }
}