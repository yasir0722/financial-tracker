<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'posted_date',
        'transaction_date',
        'transaction_detail',
        'debit',
        'credit',
        'bank_id'
    ];

    protected $casts = [
        'posted_date' => 'date',
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2'
    ];

    /**
     * Get the bank that owns the transaction.
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Get the transaction amount (credit - debit)
     */
    public function getAmountAttribute(): float
    {
        return ($this->credit ?? 0) - ($this->debit ?? 0);
    }

    /**
     * Scope to filter transactions by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
