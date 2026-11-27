<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_no' => $this->receipt_no,
            'receipt_type' => $this->receipt_type,
            'direction' => $this->direction,
            'amount' => $this->amount,
            'receipt_date' => $this->receipt_date?->toDateString(),
            'party_name' => $this->party_name,
            'description' => $this->description,
            'notes' => $this->notes,
            'source_type' => class_basename($this->receiptable_type),
            'source_id' => $this->receiptable_id,
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
