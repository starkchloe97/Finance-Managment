<?php

namespace App\Http\Requests;

use App\Enums\LoanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoanIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(LoanStatus::class)],
            'borrower' => ['nullable', 'string', 'max:255'],
            'investor_id' => ['nullable', 'integer', Rule::exists('investors', 'id')->whereNull('deleted_at')],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
