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
            'available_to_lend' => $this->resource['available_to_lend'],
            'lent_out' => $this->resource['lent_out'],
            'reserved' => $this->resource['reserved'],
            'total_capital' => $this->resource['total_capital'],
            'current_balance' => $this->resource['current_balance'],
            'transactions' => collect($this->resource['transactions'])->map(
                fn (CompanyCapitalTransaction $transaction) => [
                    'id' => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'type' => $transaction->type->value,
                    'amount' => $transaction->amount,
                    'available' => $transaction->available,
                    'transaction_date' => $transaction->transaction_date->toDateString(),
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at,
                ]
            )->values(),
            'drafts' => $this->resource['drafts'] ?? [],
            'draft_history' => $this->resource['draft_history'] ?? [],
        ];
    }
}
