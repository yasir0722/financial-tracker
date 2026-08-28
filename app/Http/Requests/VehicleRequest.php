<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'plate_number' => ['nullable', 'string', 'max:30'],
            'manufacturer' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'variant' => ['nullable', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'purchase_date' => ['nullable', 'date'],
            'current_odometer' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
