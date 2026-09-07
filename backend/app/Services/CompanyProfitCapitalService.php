<?php

namespace App\Services;

use App\Enums\CompanyCapitalTransactionType;
use App\Models\TransportJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyProfitCapitalService
{
    public function __construct(private CompanyCapitalService $capital) {}

    public function snapshot(): array
    {
        $totalProfit = $this->totalCompanyProfit();
        $transferredProfit = $this->transferredProfit();

        return [
            'total_profit' => round($totalProfit, 2),
            'transferred_profit' => round($transferredProfit, 2),
            'available_profit' => round(max($totalProfit - $transferredProfit, 0), 2),
        ];
    }

    public function addToCapital(float $amount, string $transactionDate, ?string $notes, ?User $user): array
    {
        return DB::transaction(function () use ($amount, $transactionDate, $notes, $user) {
            $account = $this->capitalAccountForUpdate();
            $totalProfit = $this->totalCompanyProfit();
            $transferredProfit = $this->transferredProfit($account->id);
            $availableProfit = round(max($totalProfit - $transferredProfit, 0), 2);

            if ($amount > $availableProfit) {
                throw ValidationException::withMessages([
                    'amount' => [
                        'Only '.number_format($availableProfit, 2).' of undistributed company profit is currently available.',
                    ],
                ]);
            }

            $this->capital->record(
                CompanyCapitalTransactionType::ProfitAdded,
                $amount,
                $transactionDate,
                null,
                $notes ?: 'Company profit transferred to available capital',
                $user,
                true,
            );

            return $this->capital->snapshot();
        });
    }

    private function totalCompanyProfit(): float
    {
        return (float) TransportJob::query()->sum('final_profit');
    }

    private function transferredProfit(?int $accountId = null): float
    {
        $query = DB::table('company_capital_transactions')
            ->where('type', CompanyCapitalTransactionType::ProfitAdded->value);

        if ($accountId !== null) {
            $query->where('company_capital_account_id', $accountId);
        }

        return (float) $query->sum('amount');
    }

    private function capitalAccountForUpdate()
    {
        $snapshot = $this->capital->snapshot();

        if (! $snapshot['account'] || ! $snapshot['initialized']) {
            throw ValidationException::withMessages([
                'capital' => ['Configure opening company capital before adding profit to capital.'],
            ]);
        }

        return $snapshot['account']->newQuery()->lockForUpdate()->findOrFail($snapshot['account']->id);
    }
}
