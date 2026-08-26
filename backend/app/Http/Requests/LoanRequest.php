<?php

namespace App\Http\Requests;

use App\Enums\LoanBorrowerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isInvestor = fn () => $this->input('borrower_type') === LoanBorrowerType::Investor->value;
        $isOutsider = fn () => $this->input('borrower_type') === LoanBorrowerType::Outsider->value;

        return [
            'borrower_type' => ['required', Rule::enum(LoanBorrowerType::class)],
            'investor_id' => [
                Rule::requiredIf($isInvestor),
                Rule::prohibitedIf($isOutsider),
                'nullable', 'integer', Rule::exists('investors', 'id')->whereNull('deleted_at'),
            ],
            'loan_borrower_id' => [
                Rule::prohibitedIf($isInvestor),
                Rule::requiredIf(fn () => $isOutsider() && ! $this->filled('outsider_name')),
                'nullable', 'integer', Rule::exists('loan_borrowers', 'id'),
            ],
            'outsider_name' => [
                Rule::prohibitedIf($isInvestor),
                Rule::requiredIf(fn () => $isOutsider() && ! $this->filled('loan_borrower_id')),
                'nullable', 'string', 'max:255',
            ],
            'outsider_email' => ['nullable', 'email', 'max:255'],
            'outsider_phone' => ['nullable', 'string', 'max:50'],
            'outsider_address' => ['nullable', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'loan_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:loan_date'],
            'notes' => ['nullable', 'string'],
            'status' => ['prohibited'],
            'paid_at' => ['prohibited'],
            'cancelled_at' => ['prohibited'],
        ];
    }
}
