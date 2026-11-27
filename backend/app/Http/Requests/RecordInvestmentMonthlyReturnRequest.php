<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordInvestmentMonthlyReturnRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('payout_location')) {
            $this->merge(['payout_location' => 'hand']);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paid_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'payout_location' => ['required', 'in:hand,bank'],
            'bank_account_id' => [
                'nullable',
                'integer',
                'required_if:payout_location,bank',
                'prohibited_if:payout_location,hand',
                'exists:bank_accounts,id',
            ],
        ];
    }
}
