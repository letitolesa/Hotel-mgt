<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'code',
        'name',
        'type',
        'category',
        'is_active',
        'is_system',
        'parent_id',
        'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    public function bankAccount()
    {
        return $this->hasOne(BankAccount::class, 'account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getBalanceAttribute()
    {
        $debits = $this->journalEntryLines()
            ->whereHas('journalEntry', function($q) {
                $q->where('status', 'posted');
            })
            ->sum('debit_amount');
            
        $credits = $this->journalEntryLines()
            ->whereHas('journalEntry', function($q) {
                $q->where('status', 'posted');
            })
            ->sum('credit_amount');
            
        return match($this->type) {
            'asset', 'expense' => $debits - $credits,
            'liability', 'equity', 'revenue' => $credits - $debits,
            default => 0
        };
    }
}