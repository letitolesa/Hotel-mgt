<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entry_number',
        'description',
        'entry_date',
        'period_year',
        'period_month',
        'reference_type',
        'reference_id',
        'is_reversal',
        'reversed_by_id',
        'reversal_date',
        'created_by',
        'approved_by',
        'approved_at',
        'status'
    ];

    protected $casts = [
        'entry_date' => 'date',
        'reversal_date' => 'datetime',
        'approved_at' => 'datetime',
        'is_reversal' => 'boolean',
        'period_year' => 'integer',
        'period_month' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by_id');
    }

    public function reconciliationEntries()
    {
        return $this->hasMany(ReconciliationEntry::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('period_year', $year)
            ->where('period_month', $month);
    }

    public function isBalanced()
    {
        $totalDebits = $this->lines()->sum('debit_amount');
        $totalCredits = $this->lines()->sum('credit_amount');
        
        return abs($totalDebits - $totalCredits) < 0.01;
    }

    public function post($userId)
    {
        if (!$this->isBalanced()) {
            throw new \Exception('Journal entry is not balanced');
        }
        
        $this->update([
            'status' => 'posted',
            'approved_by' => $userId,
            'approved_at' => now()
        ]);
    }

    public function reverse($userId, $reason = null)
    {
        $reversal = $this->replicate();
        $reversal->entry_number = 'REV-' . $this->entry_number;
        $reversal->description = ($reason ? $reason . ' - ' : '') . 'Reversal of ' . $this->entry_number;
        $reversal->is_reversal = true;
        $reversal->reversed_by_id = $userId;
        $reversal->reversal_date = now();
        $reversal->status = 'posted';
        $reversal->save();
        
        foreach ($this->lines as $line) {
            $reversal->lines()->create([
                'account_id' => $line->account_id,
                'debit_amount' => $line->credit_amount,
                'credit_amount' => $line->debit_amount,
                'description' => 'Reversal: ' . $line->description
            ]);
        }
        
        $this->update(['status' => 'reversed']);
        
        return $reversal;
    }
}