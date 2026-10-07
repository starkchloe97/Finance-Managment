<?php

namespace App\Http\Requests;

use App\Enums\InvoiceCategory;
use App\Enums\InvoiceDirection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::enum(InvoiceDirection::class)],
            'category' => ['required', Rule::enum(InvoiceCategory::class)],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'party_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'party_company' => ['nullable', 'string', 'max:255'],
            'party_contact' => ['nullable', 'string', 'max:255'],
            'party_phone' => ['nullable', 'string', 'max:40'],
            'party_address' => ['nullable', 'string'],
            'party_tax_number' => ['nullable', 'string', 'max:100'],
            'party_ntn_no' => ['nullable', 'string', 'max:100'],
            'party_str_no' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:40'],
            'company_address' => ['nullable', 'string'],
            'company_tax_number' => ['nullable', 'string', 'max:100'],
            'company_str_no' => ['nullable', 'string', 'max:100'],
            'company_ntn_no' => ['nullable', 'string', 'max:100'],
            'company_stnt_no' => ['nullable', 'string', 'max:100'],
            'company_bank_details' => ['nullable', 'string'],
            'company_logo_url' => ['nullable', 'url', 'max:2048'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'tax_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
            'tax_label' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999.999'],
            'items.*.unit' => ['nullable', 'string', 'max:40'],
            'items.*.unit_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
            'items.*.details' => ['nullable', 'array'],
        ];
    }
}
