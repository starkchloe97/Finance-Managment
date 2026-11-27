<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentMonthlyReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'investment_id' => $this->investment_id,
            'installment_number' => $this->installment_number,
            'due_date' => $this->due_date?->toDateString(),
            'amount' => $this->amount,
            'paid_amount' => $this->paid_amount,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toISOString(),
            'notes' => $this->notes,
            'payout_location' => $this->payout_location,
            'bank_account_id' => $this->bank_account_id,
        ];
    }
}
