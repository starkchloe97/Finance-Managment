<?php

namespace App\Services;

use App\Enums\ProfitDistributionStatus;
use App\Models\Investment;
use App\Models\InvestmentSettlement;
use Illuminate\Support\Facades\DB;

class InvestmentSettlementService
{
    public function calculateActualSettlement(Investment $investment): float
    {
        return DB::transaction(function () use ($investment) {
            $investment = Investment::query()->lockForUpdate()->findOrFail($investment->id);
            $distributions = (float) $investment->profitDistributions()->where('status', ProfitDistributionStatus::Confirmed)->sum('profit_amount');
            $actual = round((float) $investment->amount + $distributions - (float) $investment->deduction_amount, 2);
            InvestmentSettlement::create(['investment_id' => $investment->id, 'principal_amount' => $investment->amount, 'distribution_amount' => $distributions, 'deduction_amount' => $investment->deduction_amount, 'actual_settlement_amount' => $actual, 'calculated_at' => now()]);

            return $actual;
        });
    }
}
