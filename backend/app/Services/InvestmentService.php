<?php

namespace App\Services;

use App\Enums\InvestmentReturnType;
use App\Enums\InvestmentStatus;
use App\Models\Investment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentService
{
    public function __construct(private InvestmentMonthlyReturnService $monthlyReturns) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Investment::query()
            ->with('investor')
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): Investment
    {
        return Investment::query()
            ->with('investor')
            ->findOrFail($id);
    }

    public function create(array $data): Investment
    {
        return DB::transaction(function () use ($data) {
            $data = $this->normalizeReturnConfiguration($data);
            $data['investment_code'] = $this->generateInvestmentCode();
            $data['status'] = InvestmentStatus::Active;

            $investment = Investment::create($data);

            $this->monthlyReturns->createSchedule($investment);

            return $investment->load('investor');
        });
    }

    public function update(
        Investment $investment,
        array $data
    ): Investment {
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
                ->load('investor');
        });
    }

    public function delete(Investment $investment): void
    {
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
