<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarExpenseItem extends Model
{
    use HasFactory;

    protected $fillable = ['car_expense_id', 'category', 'item_name', 'brand', 'model', 'quantity', 'unit_price', 'labour_cost', 'total_price', 'warranty_month', 'warranty_km', 'remarks'];
    protected $casts = ['quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'labour_cost' => 'decimal:2', 'total_price' => 'decimal:2'];

    public function carExpense(): BelongsTo { return $this->belongsTo(CarExpense::class); }
}
