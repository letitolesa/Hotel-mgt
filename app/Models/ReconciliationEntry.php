<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReconciliationEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reconciliation_entries';

    protected $fillable = [
        'reconciliation_id',
        'journal_entry_id',
        'is_cleared',
        'cleared_date'
    ];

    protected $casts = [
        'is_cleared' => 'boolean',
        'cleared_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function reconciliation()
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}