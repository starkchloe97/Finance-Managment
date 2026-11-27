<?php

namespace App\Services;

use App\Enums\InvestmentStatus;
use App\Models\BankAccount;
use App\Models\Investment;
use App\Models\InvestmentMonthlyReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentMonthlyReturnService
{
    public function __construct(private AccountingPeriodService $periods) {}

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
        $this->periods->assertOpen(isset($data['paid_at'])
            ? Carbon::parse($data['paid_at'])->toDateString()
            : today()->toDateString());

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
                'payout_location' => $data['payout_location'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
            ]);

            if ($data['payout_location'] === 'bank') {
                $bankAccount = BankAccount::query()
                    ->lockForUpdate()
                    ->findOrFail($data['bank_account_id']);
                $amount = (float) $monthlyReturn->paid_amount;
                if ((float) $bankAccount->current_balance < $amount) {
                    throw ValidationException::withMessages([
                        'bank_account_id' => ['The selected bank account does not have enough funds for this payout.'],
                    ]);
                }

                $balanceAfter = round((float) $bankAccount->current_balance - $amount, 2);
                $bankAccount->update(['current_balance' => $balanceAfter]);
                $bankAccount->transactions()->create([
                    'investment_id' => $investment->id,
                    'type' => 'investment_monthly_payout',
                    'amount' => -$amount,
                    'balance_after' => $balanceAfter,
                    'description' => "Monthly return payout for investment {$investment->investment_code}",
                    'transaction_date' => $monthlyReturn->paid_at->toDateString(),
                ]);
            }

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
