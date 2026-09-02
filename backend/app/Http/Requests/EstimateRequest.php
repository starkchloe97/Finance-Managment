<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'customer_id' => 'required|exists:customers,id',
            'estimate_date' => 'required|date',
            'valid_until' => 'nullable|date',
            'pickup' => 'required',
            'destination' => 'required',
            'service_type' => 'required|in:goods,vehicle',
            'remarks' => 'nullable',

            'items' => 'required|array|min:1',

            'items.*.title' => 'required',
            'items.*.category' => 'required',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.sell_price' => 'required|numeric|min:0',
            'items.*.remarks' => 'nullable',

            /*
             * Vehicle requirements
             */
            'items.*.vehicles' => [
                'nullable',
                'array',
            ],

            'items.*.vehicles.*.source' => [
                'required',
                Rule::in(['company', 'hired']),
            ],

            'items.*.vehicles.*.asset_id' => [
                'nullable',
                'integer',
                'exists:assets,id',
            ],

            'items.*.vehicles.*.vehicle_name' => 'nullable|string|max:255',
            'items.*.vehicles.*.make' => 'nullable|string|max:255',
            'items.*.vehicles.*.model' => 'nullable|string|max:255',
            'items.*.vehicles.*.model_year' => 'nullable|integer|min:1900|max:2100',
            'items.*.vehicles.*.registration_number' => 'nullable|string|max:255',
            'items.*.vehicles.*.vin' => 'nullable|string|max:255',
            'items.*.vehicles.*.engine_number' => 'nullable|string|max:255',
            'items.*.vehicles.*.vehicle_type' => 'nullable|string|max:255',
            'items.*.vehicles.*.color' => 'nullable|string|max:255',
            'items.*.vehicles.*.notes' => 'nullable|string',
        ];
    }
}
