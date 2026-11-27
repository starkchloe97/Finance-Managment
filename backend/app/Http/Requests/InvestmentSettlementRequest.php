<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvestmentSettlementRequest extends FormRequest
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
            'payout_location' => ['required', 'in:hand,bank'],
            'bank_account_id' => [
                'nullable',
                'integer',
                'required_if:payout_location,bank',
                'prohibited_if:payout_location,hand',
                'exists:bank_accounts,id',
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
