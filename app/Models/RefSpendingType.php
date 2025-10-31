<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefSpendingType extends Model
{
    use HasFactory;

    protected $table = 'ref_spending_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'badge_class',
        'icon',
        'keywords',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'keywords' => 'array'
    ];

    /**
     * Get transactions for this spending type
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'spending_type_id');
    }

    /**
     * Scope to get only active spending types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get spending types ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get spending type options for dropdowns
     */
    public static function getOptions(): array
    {
        return self::active()->ordered()->pluck('name', 'id')->toArray();
    }

    /**
     * Get spending type by code
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }
}
