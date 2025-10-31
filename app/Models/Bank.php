<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    /**
     * Get the transactions for the bank.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the total balance for this bank
     */
    public function getTotalBalanceAttribute(): float
    {
        return $this->transactions()
            ->selectRaw('COALESCE(SUM(credit), 0) - COALESCE(SUM(debit), 0) as balance')
            ->value('balance') ?? 0;
    }
}
