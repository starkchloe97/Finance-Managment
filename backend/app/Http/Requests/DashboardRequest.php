<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in([
                'today',
                'this_week',
                'this_month',
                'last_month',
                'this_quarter',
                'this_year',
                'custom',
            ])],
            'from' => ['required_if:period,custom', 'date_format:Y-m-d'],
            'to' => ['required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }
}
