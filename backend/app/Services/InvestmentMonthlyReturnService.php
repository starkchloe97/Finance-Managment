<?php

namespace App\Services;

use App\Enums\InvestmentStatus;
use App\Models\Investment;
use App\Models\InvestmentMonthlyReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentMonthlyReturnService
{
    public function createSchedule(Investment $investment): void
    {
        $monthlyAmount = $investment->calculated_return_amount;

        for ($installment = 1; $installment <= $investment->period_months; $installment++) {
            InvestmentMonthlyReturn::create([
                'investment_id' => $investment->id,
                'installment_number' => $installment,
                'due_date' => $investment->investment_date->copy()->addMonthsNoOverflow($installment),
                'amount' => $monthlyAmount,
            ]);
        }
    }

    public function recordPayment(
        Investment $investment,
        InvestmentMonthlyReturn $monthlyReturn,
        array $data
    ): InvestmentMonthlyReturn {
        return DB::transaction(function () use ($investment, $monthlyReturn, $data) {
            $investment = Investment::query()->lockForUpdate()->findOrFail($investment->id);
            $monthlyReturn = InvestmentMonthlyReturn::query()
                ->lockForUpdate()
                ->where('investment_id', $investment->id)
                ->findOrFail($monthlyReturn->id);

            if (! in_array($investment->status, [InvestmentStatus::Active, InvestmentStatus::Matured], true)) {
                throw ValidationException::withMessages([
                    'investment' => ['Monthly returns can only be paid for active or matured investments.'],
                ]);
            }

            if ($monthlyReturn->status !== 'pending') {
                throw ValidationException::withMessages([
                    'monthly_return' => ['This monthly return has already been processed.'],
                ]);
            }

            if ($monthlyReturn->due_date->isAfter(today())) {
                throw ValidationException::withMessages([
                    'monthly_return' => ['This monthly return is not due yet.'],
                ]);
            }

            $monthlyReturn->update([
                'status' => 'paid',
                'paid_amount' => $monthlyReturn->amount,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            return $monthlyReturn->refresh();
        });
    }

    public function cancelPendingReturns(Investment $investment): void
    {
        $investment->monthlyReturns()
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }
}
