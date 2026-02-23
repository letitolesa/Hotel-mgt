<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationBroadcast extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'notification_id',
        'target_type',
        'role_id',
        'permission_id',
        'department_id',
        'total_recipients'
    ];

    protected $casts = [
        'total_recipients' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}