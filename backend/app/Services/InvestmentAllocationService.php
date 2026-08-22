<?php

namespace App\Services;

use App\Enums\AllocationStatus;
use App\Enums\InvestmentCategory;
use App\Enums\InvestmentStatus;
use App\Models\Investment;
use App\Models\InvestmentAllocation;
use App\Models\TransportJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentAllocationService
{
    public function allocate(Investment $investment, TransportJob $job, float $amount, ?string $notes = null): InvestmentAllocation
    {
        return DB::transaction(function () use ($investment, $job, $amount, $notes) {
            $investment = Investment::query()->lockForUpdate()->findOrFail($investment->id);

            if ($investment->investment_category === InvestmentCategory::Pool) $this->reject('investment', 'Pool investments cannot be allocated to jobs.');
            if ($amount <= 0) $this->reject('amount', 'Allocation amount must be greater than zero.');
            if ($investment->status !== InvestmentStatus::Active) $this->reject('investment', 'Only active investments can receive allocations.');
            if ($job->financially_locked_at) $this->reject('job', 'Financially locked jobs cannot receive allocations.');
            if ($job->status->value === 'completed') $this->reject('job', 'Completed jobs cannot receive allocations.');
            if ($amount > $investment->remaining_capital) $this->reject('amount', 'Allocation amount exceeds the remaining capital.');

            return InvestmentAllocation::create([
                'investment_id' => $investment->id, 'transport_job_id' => $job->id, 'amount' => $amount,
                'status' => AllocationStatus::Active, 'allocated_at' => now(), 'notes' => $notes,
            ])->load(['investment.investor', 'transportJob']);
        });
    }

    public function release(InvestmentAllocation $allocation): void { $this->changeStatus($allocation, AllocationStatus::Released); }
    public function cancel(InvestmentAllocation $allocation): void { $this->changeStatus($allocation, AllocationStatus::Cancelled); }

    private function changeStatus(InvestmentAllocation $allocation, AllocationStatus $status): void
    {
        DB::transaction(function () use ($allocation, $status) {
            $allocation = InvestmentAllocation::query()->lockForUpdate()->findOrFail($allocation->id);
            if ($allocation->transportJob()->value('financially_locked_at')) $this->reject('allocation', 'Allocations on financially locked jobs cannot change.');
            if ($allocation->status !== AllocationStatus::Active) $this->reject('allocation', 'Only active allocations can change status.');
            $allocation->update(['status' => $status]);
        });
    }

    private function reject(string $field, string $message): never { throw ValidationException::withMessages([$field => [$message]]); }
}
