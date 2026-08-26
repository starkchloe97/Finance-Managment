<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoanUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'amount' => ['prohibited'],
            'loan_date' => ['prohibited'],
            'borrower_type' => ['prohibited'],
            'investor_id' => ['prohibited'],
            'loan_borrower_id' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
