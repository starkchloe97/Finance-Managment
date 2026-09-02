<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfitDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['investment_id' => ['required', 'integer', Rule::exists('investments', 'id')->whereNull('deleted_at')], 'notes' => ['nullable', 'string']];
    }
}
