<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstimateRequest extends FormRequest
{
    /**
     * Same rules as creating an estimate, plus the status field — the status
     * is part of the quote lifecycle (draft/sent/rejected/expired) and can be
     * set directly, unlike a job whose stages only advance server-side.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'estimate_type' => 'sometimes|in:rental,transportation,repair',
            'estimate_date' => 'required|date',
            'valid_until' => 'nullable|date',
            'pickup' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'service_type' => 'required|in:goods,vehicle',
            'tonnage' => 'nullable|numeric|decimal:0,3|min:0|max:999999.999',
            'status' => 'sometimes|in:draft,sent,accepted,rejected,expired',
            'details' => 'nullable|array',
            'details.customer_company' => 'nullable|string|max:255',
            'details.customer_location' => 'nullable|string|max:255',
            'details.job_name' => 'nullable|string|max:255',
            'details.vehicle_required' => 'nullable|string|max:255',
            'details.vehicle_model' => 'nullable|string|max:255',
            'details.quantity' => 'nullable|numeric|min:0',
            'details.service_mode' => 'nullable|string|max:255',
            'details.route_location' => 'nullable|string|max:255',
            'details.duty_hours' => 'nullable|string|max:255',
            'details.monthly_daily_km' => 'nullable|string|max:255',
            'details.contract_period' => 'nullable|string|max:255',
            'details.job_type' => 'nullable|in:staff,goods',
            'details.vehicle_type' => 'nullable|string|max:255',
            'details.vehicle_quantity' => 'nullable|numeric|min:0',
            'details.route' => 'nullable|string|max:255',
            'details.daily_running_km' => 'nullable|numeric|min:0',
            'details.working_days' => 'nullable|numeric|min:0',
            'details.vehicle_make' => 'nullable|string|max:255',
            'details.registration_number' => 'nullable|string|max:255',
            'details.engine_number' => 'nullable|string|max:255',
            'details.current_km' => 'nullable|numeric|min:0',
            'details.job_location' => 'nullable|string|max:255',
            'details.estimated_completion_days' => 'nullable|numeric|min:0',
            'details.workshop_charges' => 'nullable|numeric|min:0',
            'details.towing_charges' => 'nullable|numeric|min:0',
            'details.other_expenses' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'terms' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:255',
            'prepared_by' => 'nullable|string|max:255',
            'checked_by' => 'nullable|string|max:255',
            'approved_by' => 'nullable|string|max:255',
            'customer_approval' => 'nullable|string|max:255',
            'remarks' => 'nullable',
            'items' => 'required|array|min:1',
            'items.*.title' => 'required',
            'items.*.category' => 'required',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.sell_price' => 'required|numeric|min:0',
            'items.*.parts_cost' => 'nullable|numeric|min:0',
            'items.*.labor_cost' => 'nullable|numeric|min:0',
            'items.*.remarks' => 'nullable',
            'items.*.vehicles' => ['nullable', 'array'],
            'items.*.vehicles.*.source' => ['required', Rule::in(['company', 'hired'])],
            'items.*.vehicles.*.asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'items.*.vehicles.*.vehicle_name' => 'nullable|string|max:255',
            'items.*.vehicles.*.supplier_name' => 'nullable|string|max:255',
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
