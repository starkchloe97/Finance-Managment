<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinancialAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field' => ['required', 'string', 'max:255'],
            'old_value' => ['nullable', 'string'],
            'new_value' => ['nullable', 'string'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
