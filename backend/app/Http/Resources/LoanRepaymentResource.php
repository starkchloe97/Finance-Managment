<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanRepaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loan_id' => $this->loan_id,
            'amount' => $this->amount,
            'payment_date' => $this->payment_date?->toDateString(),
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
        ];
    }
}
