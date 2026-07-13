<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type'
    ];

    protected $casts = [
        'type' => 'boolean'
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

    /**
     * Scope to get only banks (type = true)
     */
    public function scopeBanks($query)
    {
        return $query->where('type', true);
    }

    /**
     * Scope to get only other financial institutions (type = false)
     */
    public function scopeOtherInstitutions($query)
    {
        return $query->where('type', false);
    }

    /**
     * Get the display type
     */
    public function getTypeDisplayAttribute(): string
    {
        return $this->type ? 'Bank' : 'Financial Institution';
    }
}
