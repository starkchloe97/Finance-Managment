<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'estimate_date' => 'required|date',
            'valid_until' => 'nullable|date',
            'pickup' => 'required',
            'destination' => 'required',
            'service_type' => 'required|in:goods,vehicle',
            'status' => 'sometimes|in:draft,sent,accepted,rejected,expired',
            'remarks' => 'nullable',
            'items' => 'required|array|min:1',
            'items.*.title' => 'required',
            'items.*.category' => 'required',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.sell_price' => 'required|numeric|min:0',
            'items.*.remarks' => 'nullable',
        ];
    }
}
