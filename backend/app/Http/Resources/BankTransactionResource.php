<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => $this->amount,
            'balance_after' => $this->balance_after,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'payment_id' => $this->payment_id,
            'payment_no' => $this->payment?->payment_no,
            'created_at' => $this->created_at,
        ];
    }
}
