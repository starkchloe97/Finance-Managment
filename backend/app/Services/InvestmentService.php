<?php

namespace App\Services;

use App\Enums\InvestmentReturnType;
use App\Enums\InvestmentStatus;
use App\Models\BankAccount;
use App\Models\Investment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentService
{
    public function __construct(
        private InvestmentMonthlyReturnService $monthlyReturns,
        private ReceiptService $receipts,
        private AccountingPeriodService $periods,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Investment::query()
            ->with(['investor', 'bankAccount', 'receipt'])
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): Investment
    {
        return Investment::query()
            ->with(['investor', 'bankAccount', 'receipt'])
            ->findOrFail($id);
    }

    public function create(array $data): Investment
    {
        $this->periods->assertOpen($data['investment_date'] ?? null);

        return DB::transaction(function () use ($data) {
            $data = $this->normalizeReturnConfiguration($data);
            $data['investment_code'] = $this->generateInvestmentCode();
            $data['status'] = InvestmentStatus::Active;

            $investment = Investment::create($data);

            $this->recordBankReceipt($investment);
            $this->receipts->issue(
                $investment,
                'investment_receipt',
                'received',
                (float) $investment->amount,
                $investment->investment_date->toDateString(),
                "Investment {$investment->investment_code} received",
                $investment->investor->name,
                $investment->notes,
            );

            $this->monthlyReturns->createSchedule($investment);

            return $investment->load(['investor', 'bankAccount', 'receipt']);
        });
    }

    public function update(
        Investment $investment,
        array $data
    ): Investment {
        $this->periods->assertOpen($investment->investment_date?->toDateString());
        $this->periods->assertOpen($data['investment_date'] ?? null);

        return DB::transaction(function () use (
            $investment,
            $data
        ) {
            $investment = Investment::query()->lockForUpdate()->findOrFail($investment->id);

            if ($investment->status !== InvestmentStatus::Active) {
                throw ValidationException::withMessages([
                    'investment' => ['Only active investments can be edited.'],
                ]);
            }

            $data = $this->normalizeReturnConfiguration($data);
            $investment->fill($data);

            $scheduleFields = [
                'investment_date',
                'amount',
                'return_type',
                'return_percentage',
                'fixed_return_amount',
                'period_months',
            ];

            if ($investment->isDirty($scheduleFields)) {
                if ($investment->monthlyReturns()->where('status', 'paid')->exists()) {
                    throw ValidationException::withMessages([
                        'investment' => ['Return terms cannot change after a monthly return has been paid.'],
                    ]);
                }

                $investment->monthlyReturns()->delete();
                $investment->save();
                $this->monthlyReturns->createSchedule($investment);
            } else {
                $investment->save();
            }

            return $investment
                ->refresh()
                ->load(['investor', 'bankAccount', 'receipt']);
        });
    }

    public function delete(Investment $investment): void
    {
        $this->periods->assertOpen($investment->investment_date?->toDateString());

        DB::transaction(function () use ($investment) {
            $investment = Investment::query()->lockForUpdate()->findOrFail($investment->id);

            if ($investment->status !== InvestmentStatus::Active) {
                throw ValidationException::withMessages([
                    'investment' => ['Only active investments can be deleted.'],
                ]);
            }

            $investment->delete();
        });
    }

    private function normalizeReturnConfiguration(array $data): array
    {
        $returnType = $data['return_type'] instanceof InvestmentReturnType
            ? $data['return_type']->value
            : $data['return_type'];

        if ($returnType === InvestmentReturnType::Fixed->value) {
            $data['return_percentage'] = null;
        } else {
            $data['fixed_return_amount'] = null;
        }

        return $data;
    }

    private function recordBankReceipt(Investment $investment): void
    {
        if ($investment->funds_location !== 'bank') {
            return;
        }

        $bankAccount = BankAccount::query()
            ->lockForUpdate()
            ->findOrFail($investment->bank_account_id);
        $balanceAfter = round((float) $bankAccount->current_balance + (float) $investment->amount, 2);

        if ($balanceAfter > 9999999999999.99) {
            throw ValidationException::withMessages([
                'bank_account_id' => ['This receipt would exceed the maximum bank account balance.'],
            ]);
        }

        $bankAccount->update(['current_balance' => $balanceAfter]);
        $bankAccount->transactions()->create([
            'investment_id' => $investment->id,
            'type' => 'investment_receipt',
            'amount' => $investment->amount,
            'balance_after' => $balanceAfter,
            'description' => "Investment {$investment->investment_code} received",
            'transaction_date' => $investment->investment_date,
        ]);
    }

    private function generateInvestmentCode(): string
    {
        $lastInvestment = Investment::query()
            ->withTrashed()
            ->latest('id')
            ->first();

        $nextNumber = ($lastInvestment?->id ?? 0) + 1;

        return 'INVEST-'.str_pad(
            $nextNumber,
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}
