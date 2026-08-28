<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'posted_date',
        'transaction_date',
        'transaction_detail',
        'debit',
        'credit',
        'balance',
        'bank_id',
        'spending_type_id',
        'is_locked'
    ];

    protected $casts = [
        'posted_date' => 'date',
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_locked' => 'boolean'
    ];

    /**
     * Get the bank that owns the transaction.
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the spending type for this transaction.
     */
    public function spendingType(): BelongsTo
    {
        return $this->belongsTo(RefSpendingType::class, 'spending_type_id');
    }

    public function carExpense()
    {
        return $this->hasOne(CarExpense::class);
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

    /**
     * Get formatted spending type name
     */
    public function getSpendingTypeNameAttribute(): string
    {
        return $this->spendingType?->name ?? 'Not Set';
    }

    /**
     * Get spending type badge class
     */
    public function getSpendingTypeBadgeClassAttribute(): string
    {
        return $this->spendingType?->badge_class ?? 'badge-secondary';
    }

    /**
     * Get spending type code
     */
    public function getSpendingTypeCodeAttribute(): ?string
    {
        return $this->spendingType?->code;
    }

    /**
     * Get spending type icon
     */
    public function getSpendingTypeIconAttribute(): ?string
    {
        return $this->spendingType?->icon;
    }

    /**
     * Scope to filter by spending type
     */
    public function scopeOfType($query, $typeId)
    {
        return $query->where('spending_type_id', $typeId);
    }
}
