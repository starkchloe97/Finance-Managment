<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'direction' => $this->direction->value,
            'category' => $this->category,
            'customer_id' => $this->customer_id,
            'party_name' => $this->party_name,
            'party_company' => $this->party_company,
            'party_contact' => $this->party_contact,
            'party_phone' => $this->party_phone,
            'party_address' => $this->party_address,
            'party_tax_number' => $this->party_tax_number,
            'party_ntn_no' => $this->party_ntn_no ?? $this->party_tax_number,
            'party_str_no' => $this->party_str_no,
            'company_name' => $this->company_name ?? config('invoice.company_name'),
            'company_phone' => $this->company_phone,
            'company_address' => $this->company_address,
            'company_tax_number' => $this->company_tax_number,
            'company_str_no' => $this->company_str_no ?? $this->tax_number,
            'company_ntn_no' => $this->company_ntn_no ?? $this->company_tax_number,
            'company_stnt_no' => $this->company_stnt_no,
            'company_bank_details' => $this->company_bank_details ?? config('invoice.company_bank_details'),
            'company_logo_url' => $this->company_logo_url ?? config('invoice.company_logo_url'),
            'source' => $this->sourceData(),
            'transport_job_id' => $this->transport_job_id,
            'job_expense_id' => $this->job_expense_id,
            'vehicle_contract_id' => $this->vehicle_contract_id,
            'estimate_item_vehicle_id' => $this->estimate_item_vehicle_id,
            'billing_period' => $this->billing_period?->toDateString(),
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'tax_label' => $this->tax_label,
            'tax_number' => $this->tax_number,
            'total' => $this->total,
            'paid_amount' => $this->paid_amount,
            'outstanding_amount' => $this->outstanding_amount,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => $this->whenLoaded('allocations', fn () => $this->allocations->map(fn ($allocation) => [
                'id' => $allocation->payment->id,
                'payment_no' => $allocation->payment->payment_no,
                'direction' => $allocation->payment->direction->value,
                'payment_date' => $allocation->payment->payment_date?->toDateString(),
                'amount' => $allocation->amount,
                'payment_method' => $allocation->payment->payment_method,
                'reference' => $allocation->payment->reference,
            ])->values()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function sourceData(): ?array
    {
        if ($this->relationLoaded('transportJob') && $this->transportJob) {
            return [
                'type' => 'transport_job',
                'id' => $this->transportJob->id,
                'code' => $this->transportJob->code,
            ];
        }

        if ($this->relationLoaded('jobExpense') && $this->jobExpense) {
            return [
                'type' => 'job_expense',
                'id' => $this->jobExpense->id,
                'title' => $this->jobExpense->title,
                'job_id' => $this->jobExpense->transportJob?->id,
                'job_code' => $this->jobExpense->transportJob?->code,
            ];
        }

        if ($this->relationLoaded('hiredVehicle') && $this->hiredVehicle) {
            $vehicle = $this->hiredVehicle;
            $item = $vehicle->estimateItem;
            $job = $item?->estimate?->transportJob;

            return [
                'type' => 'hired_vehicle',
                'id' => $vehicle->id,
                'supplier_name' => $vehicle->supplier_name,
                'vehicle_name' => $vehicle->vehicle_name,
                'registration_number' => $vehicle->registration_number,
                'estimate_item_id' => $item?->id,
                'job_id' => $job?->id,
                'job_code' => $job?->code,
            ];
        }

        if ($this->relationLoaded('vehicleContract') && $this->vehicleContract) {
            return [
                'type' => 'vehicle_contract',
                'id' => $this->vehicleContract->id,
                'contract_number' => $this->vehicleContract->contract_number,
                'billing_period' => $this->billing_period?->format('Y-m'),
            ];
        }

        return null;
    }
}
