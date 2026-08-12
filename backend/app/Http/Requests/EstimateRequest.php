<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EstimateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'customer_id'=>'required|exists:customers,id',

            'estimate_date'=>'required|date',

            'pickup'=>'required',

            'destination'=>'required',

            'service_type'=>'required|in:goods,vehicle',

            'margin'=>'nullable|numeric',

            'margin_type'=>'nullable|in:fixed,percentage',

            'remarks'=>'nullable',

            'items'=>'required|array|min:1',

            'items.*.title'=>'required',

            'items.*.category'=>'required',

            'items.*.quantity'=>'required|numeric|min:1',

            'items.*.unit_price'=>'required|numeric|min:0'

        ];
    }
}
