<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'name', 'plate_number', 'manufacturer', 'model', 'variant', 'year', 'purchase_date', 'current_odometer', 'is_default'];

    protected $casts = ['purchase_date' => 'date', 'is_default' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function carExpenses(): HasMany { return $this->hasMany(CarExpense::class); }
    public function getDisplayNameAttribute(): string
    {
        return trim($this->name . ($this->plate_number ? ' (' . $this->plate_number . ')' : ''));
    }
}
