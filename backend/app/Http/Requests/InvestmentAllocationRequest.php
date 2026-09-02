<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestmentAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['transport_job_id' => ['required', 'integer', Rule::exists('transport_jobs', 'id')->whereNull('deleted_at')], 'amount' => ['required', 'numeric', 'gt:0'], 'notes' => ['nullable', 'string']];
    }
}
