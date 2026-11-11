<?php

namespace App\Http\Requests;

use App\Models\Query;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'service' => ['nullable', 'string', 'max:160'],
            'pickup_location' => ['nullable', 'string', 'max:160'],
            'dropoff_location' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(Query::STATUSES)],
        ];
    }
}
