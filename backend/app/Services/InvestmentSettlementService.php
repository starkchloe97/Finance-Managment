<?php

namespace App\Services;

use App\Enums\ProfitDistributionStatus;
use App\Models\Investment;
use App\Models\InvestmentSettlement;
use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentSettlementService
{
    public function calculateActualSettlement(Investment $investment, array $data = []): float
    {
        return DB::transaction(function () use ($investment, $data) {
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

                $settlement = InvestmentSettlement::create([
                    'investment_id' => $investment->id,
                    'principal_amount' => $investment->amount,
                    'distribution_amount' => $monthlyReturns,
                    'deduction_amount' => $investment->deduction_amount,
                    'actual_settlement_amount' => $actual,
                    'payout_location' => $data['payout_location'] ?? null,
                    'bank_account_id' => $data['bank_account_id'] ?? null,
                    'calculated_at' => now(),
                ]);

                $this->recordBankPayout($investment, $settlement);
                return $actual;
            }

            $distributions = (float) $investment->profitDistributions()->where('status', ProfitDistributionStatus::Confirmed)->sum('profit_amount');
            $actual = round((float) $investment->amount + $distributions - (float) $investment->deduction_amount, 2);
            $settlement = InvestmentSettlement::create([
                'investment_id' => $investment->id,
                'principal_amount' => $investment->amount,
                'distribution_amount' => $distributions,
                'deduction_amount' => $investment->deduction_amount,
                'actual_settlement_amount' => $actual,
                'payout_location' => $data['payout_location'] ?? null,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'calculated_at' => now(),
            ]);

            $this->recordBankPayout($investment, $settlement);
            return $actual;
        });
    }

    private function recordBankPayout(Investment $investment, InvestmentSettlement $settlement): void
    {
        if ($settlement->payout_location !== 'bank') {
            return;
        }

        $bankAccount = BankAccount::query()
            ->lockForUpdate()
            ->findOrFail($settlement->bank_account_id);
        $amount = (float) $settlement->actual_settlement_amount;
        if ((float) $bankAccount->current_balance < $amount) {
            throw ValidationException::withMessages([
                'bank_account_id' => ['The selected bank account does not have enough funds for this settlement.'],
            ]);
        }

        $balanceAfter = round((float) $bankAccount->current_balance - $amount, 2);
        $bankAccount->update(['current_balance' => $balanceAfter]);
        $bankAccount->transactions()->create([
            'investment_id' => $investment->id,
            'type' => 'investment_settlement_payout',
            'amount' => -$amount,
            'balance_after' => $balanceAfter,
            'description' => "Settlement payout for investment {$investment->investment_code}",
            'transaction_date' => today()->toDateString(),
        ]);
    }
}
