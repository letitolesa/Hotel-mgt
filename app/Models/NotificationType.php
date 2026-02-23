<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'color',
        'is_email',
        'is_active'
    ];

    protected $casts = [
        'is_email' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function userSettings()
    {
        return $this->hasMany(UserNotificationSetting::class);
    }

    public function roleDefaults()
    {
        return $this->hasMany(RoleNotificationDefault::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEmailEnabled($query)
    {
        return $query->where('is_email', true);
    }
}