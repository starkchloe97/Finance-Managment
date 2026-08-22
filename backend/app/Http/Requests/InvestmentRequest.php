<?php

namespace App\Http\Requests;

use App\Enums\InvestmentCategory;
use App\Enums\InvestmentReturnType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'investor_id' => [
                $this->isMethod('post') ? 'required' : 'prohibited',
                'integer',
                Rule::exists('investors', 'id')->whereNull('deleted_at'),
            ],
            'investment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'investment_category' => ['required', Rule::enum(InvestmentCategory::class)],
            'return_type' => [
                'required',
                Rule::enum(InvestmentReturnType::class),
                Rule::prohibitedIf(
                    fn () => $this->input('investment_category') === InvestmentCategory::Pool->value
                        && $this->input('return_type') === InvestmentReturnType::Fixed->value
                ),
            ],
            'return_percentage' => [
                Rule::prohibitedIf(
                    fn () => $this->input('return_type') === InvestmentReturnType::Fixed->value
                        && $this->filled('return_percentage')
                ),
                Rule::requiredIf(
                    fn () => $this->input('investment_category') === InvestmentCategory::Pool->value
                        || $this->input('return_type') === InvestmentReturnType::Percentage->value
                ),
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'fixed_return_amount' => [
                Rule::prohibitedIf(
                    fn () => $this->input('return_type') === InvestmentReturnType::Percentage->value
                        && $this->filled('fixed_return_amount')
                ),
                Rule::requiredIf(
                    fn () => $this->input('return_type') === InvestmentReturnType::Fixed->value
                ),
                'nullable',
                'numeric',
                'min:0',
            ],
            'period_months' => ['required', 'integer', 'min:1'],
            'return_policy_days' => ['nullable', 'integer', 'min:1'],
            'status' => ['prohibited'],
            'matured_at' => ['prohibited'],
            'withdrawn_at' => ['prohibited'],
            'settled_at' => ['prohibited'],
            'cancelled_at' => ['prohibited'],
            'deduction_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'calculated_return_amount' => ['prohibited'],
            'maximum_return_amount' => ['prohibited'],
        ];
    }
}
