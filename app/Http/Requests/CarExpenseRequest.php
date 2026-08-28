<?php

namespace App\Http\Requests;

use App\Models\CarExpense;
use Illuminate\Foundation\Http\FormRequest;

class CarExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expense = $this->route('carExpense');
        return auth()->check() && (!$expense || $expense->transaction?->user_id === auth()->id());
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'service_date' => ['required', 'date'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'workshop_existing' => ['nullable', 'string', 'max:150'],
            'workshop_new' => ['nullable', 'string', 'max:150'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'next_service_km' => ['nullable', 'integer', 'min:0'],
            'next_service_date' => ['nullable', 'date'],
            'note_title' => ['nullable', 'string', 'max:150'],
            'foreman_technician' => ['nullable', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category' => ['required', 'string', 'max:80'],
            'items.*.item_name' => ['required', 'string', 'max:150'],
            'items.*.brand' => ['nullable', 'string', 'max:100'],
            'items.*.model' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.labour_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.warranty_month' => ['nullable', 'integer', 'min:0'],
            'items.*.warranty_km' => ['nullable', 'integer', 'min:0'],
            'items.*.remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
