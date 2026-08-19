<?php

namespace App\Http\Requests;

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

            'investment_date' => [
                'required',
                'date',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'period_months' => [
                'required',
                'integer',
                'min:1',
            ],

            'return_policy_days' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'min_return_percent' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'max_return_percent' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'gte:min_return_percent',
            ],

            'status' => ['prohibited'],

            'matured_at' => [
                'prohibited',
            ],

            'withdrawn_at' => [
                'prohibited',
            ],

            'settled_at' => ['prohibited'],
            'cancelled_at' => ['prohibited'],

            'deduction_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
