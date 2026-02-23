<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankReconciliation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_account_id',
        'statement_date',
        'statement_balance',
        'book_balance',
        'status',
        'reconciled_by',
        'reconciled_at',
        'notes'
    ];

    protected $casts = [
        'statement_date' => 'date',
        'statement_balance' => 'decimal:2',
        'book_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'reconciled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function reconciledBy()
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function entries()
    {
        return $this->hasMany(ReconciliationEntry::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function addEntry($journalEntryId, $isCleared = false, $clearedDate = null)
    {
        return $this->entries()->create([
            'journal_entry_id' => $journalEntryId,
            'is_cleared' => $isCleared,
            'cleared_date' => $clearedDate
        ]);
    }

    public function complete($userId)
    {
        $this->update([
            'status' => 'reconciled',
            'reconciled_by' => $userId,
            'reconciled_at' => now()
        ]);
    }
}