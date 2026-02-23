<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
        'bank_name',
        'branch_name',
        'account_name',
        'account_number',
        'iban',
        'swift_code',
        'currency',
        'opening_balance',
        'current_balance',
        'is_active'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function reconciliations()
    {
        return $this->hasMany(BankReconciliation::class);
    }

    public function scopeActive($query)
    {
        return $this->where('is_active', true);
    }

    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    public function updateBalance()
    {
        $totalDebits = JournalEntryLine::whereHas('journalEntry', function($q) {
                $q->where('status', 'posted');
            })
            ->where('account_id', $this->account_id)
            ->sum('debit_amount');
            
        $totalCredits = JournalEntryLine::whereHas('journalEntry', function($q) {
                $q->where('status', 'posted');
            })
            ->where('account_id', $this->account_id)
            ->sum('credit_amount');
            
        $this->current_balance = $this->opening_balance + $totalDebits - $totalCredits;
        $this->save();
        
        return $this->current_balance;
    }
}