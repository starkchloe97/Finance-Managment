<?php

namespace App\Services;

use App\Enums\ProfitDistributionStatus;
use App\Models\Investment;
use App\Models\InvestmentSettlement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentSettlementService
{
    public function calculateActualSettlement(Investment $investment): float
    {
        return DB::transaction(function () use ($investment) {
            $investment = Investment::query()->lockForUpdate()->findOrFail($investment->id);

            if ($investment->monthlyReturns()->exists()) {
                if ($investment->monthlyReturns()->where('status', 'pending')->exists()) {
                    throw ValidationException::withMessages([
                        'investment' => ['All due monthly returns must be paid or cancelled before settlement.'],
                    ]);
                }

                $monthlyReturns = (float) $investment->monthlyReturns()
                    ->where('status', 'paid')
                    ->sum('paid_amount');
                $actual = round((float) $investment->amount - (float) $investment->deduction_amount, 2);

                InvestmentSettlement::create([
                    'investment_id' => $investment->id,
                    'principal_amount' => $investment->amount,
                    'distribution_amount' => $monthlyReturns,
                    'deduction_amount' => $investment->deduction_amount,
                    'actual_settlement_amount' => $actual,
                    'calculated_at' => now(),
                ]);

                return $actual;
            }

            $distributions = (float) $investment->profitDistributions()->where('status', ProfitDistributionStatus::Confirmed)->sum('profit_amount');
            $actual = round((float) $investment->amount + $distributions - (float) $investment->deduction_amount, 2);
            InvestmentSettlement::create(['investment_id' => $investment->id, 'principal_amount' => $investment->amount, 'distribution_amount' => $distributions, 'deduction_amount' => $investment->deduction_amount, 'actual_settlement_amount' => $actual, 'calculated_at' => now()]);

            return $actual;
        });
    }
}
