<?php

namespace App\Http\Resources;

use App\Enums\CompanyCapitalTransactionType;
use App\Models\CompanyCapitalTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyCapitalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $accountId = $this->resource['account']?->id;
        $profitAdded = $accountId
            ? (float) CompanyCapitalTransaction::query()
                ->where('company_capital_account_id', $accountId)
                ->where('type', CompanyCapitalTransactionType::ProfitAdded->value)
                ->sum('amount')
            : 0.0;

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
            'total_capital' => round((float) $this->resource['total_capital'] + $profitAdded, 2),
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
