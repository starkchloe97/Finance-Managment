<?php

namespace App\Services;

use App\Enums\InvestmentStatus;
use App\Models\Investment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentLifecycleService
{
    public function __construct(private InvestmentSettlementService $settlements) {}

    public function mature(Investment $investment): Investment
    {
        return $this->transition(
            $investment,
            InvestmentStatus::Matured,
            [InvestmentStatus::Active],
            'matured_at',
            function (Investment $investment): void {
                if ($investment->maturity_date?->isAfter(today())) {
                    throw ValidationException::withMessages([
                        'status' => ['This investment cannot mature before its maturity date.'],
                    ]);
                }
            }
        );
    }

    public function withdraw(Investment $investment): Investment
    {
        return $this->transition(
            $investment,
            InvestmentStatus::Withdrawn,
            [InvestmentStatus::Active],
            'withdrawn_at'
        );
    }

    public function settle(Investment $investment): Investment
    {
        return DB::transaction(function () use ($investment) {
            $this->settlements->calculateActualSettlement($investment);

            return $this->transition(
                $investment,
                InvestmentStatus::Settled,
                [InvestmentStatus::Matured, InvestmentStatus::Withdrawn],
                'settled_at'
            );
        });
    }

    public function cancel(Investment $investment): Investment
    {
        return $this->transition(
            $investment,
            InvestmentStatus::Cancelled,
            [InvestmentStatus::Active],
            'cancelled_at'
        );
    }

    private function transition(
        Investment $investment,
        InvestmentStatus $target,
        array $allowedStatuses,
        string $timestampColumn,
        ?callable $beforeTransition = null
    ): Investment {
        return DB::transaction(function () use (
            $investment,
            $target,
            $allowedStatuses,
            $timestampColumn,
            $beforeTransition
        ) {
            $investment = Investment::query()->lockForUpdate()->findOrFail($investment->id);

            if (! in_array($investment->status, $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'status' => [
                        "Investment cannot transition from '{$investment->status->value}' using this action.",
                    ],
                ]);
            }

            if ($beforeTransition !== null) {
                $beforeTransition($investment);
            }

            $investment->update([
                'status' => $target,
                $timestampColumn => now(),
            ]);

            return $investment->refresh()->load('investor');
        });
    }
}
