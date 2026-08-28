<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['transaction_id', 'vehicle_id', 'service_date', 'odometer', 'workshop', 'invoice_number', 'next_service_km', 'next_service_date', 'notes'];
    protected $casts = ['service_date' => 'date', 'next_service_date' => 'date'];

    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function items(): HasMany { return $this->hasMany(CarExpenseItem::class); }
    public function getTotalAttribute(): float { return (float) $this->items->sum('total_price'); }

    public function getNotesDataAttribute(): array
    {
        $notes = $this->notes;
        if (!$notes) return ['title' => '', 'foreman_technician' => ''];

        $decoded = json_decode($notes, true);
        return is_array($decoded)
            ? array_merge(['title' => '', 'foreman_technician' => ''], $decoded)
            : ['title' => $notes, 'foreman_technician' => ''];
    }
}
