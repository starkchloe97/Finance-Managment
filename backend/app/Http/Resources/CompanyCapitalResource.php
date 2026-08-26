<?php

namespace App\Http\Resources;

use App\Models\CompanyCapitalTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyCapitalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'initialized' => $this->resource['initialized'],
            'account' => $this->resource['account'] ? [
                'id' => $this->resource['account']->id,
                'code' => $this->resource['account']->code,
                'name' => $this->resource['account']->name,
            ] : null,
            'opening_balance' => $this->resource['opening_balance'],
            'current_balance' => $this->resource['current_balance'],
            'transactions' => collect($this->resource['transactions'])->map(
                fn (CompanyCapitalTransaction $transaction) => [
                    'id' => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'type' => $transaction->type->value,
                    'amount' => $transaction->amount,
                    'transaction_date' => $transaction->transaction_date->toDateString(),
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at,
                ]
            )->values(),
        ];
    }
}
