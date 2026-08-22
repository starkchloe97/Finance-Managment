<?php

namespace App\Services;

use App\Enums\AllocationStatus;
use App\Enums\ProfitDistributionStatus;
use App\Models\Investment;
use App\Models\InvestorProfitDistribution;
use App\Models\TransportJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestorPayoutService
{
    public function calculateDistribution(TransportJob $job, Investment $investment, ?string $notes = null): InvestorProfitDistribution
    {
        return DB::transaction(function () use ($job, $investment, $notes) {
            $job = TransportJob::query()->lockForUpdate()->findOrFail($job->id);
            if ($job->financially_locked_at) {
                $this->reject('job', 'This job is financially locked.');
            }
            if (InvestorProfitDistribution::query()->where('investment_id', $investment->id)->where('transport_job_id', $job->id)->exists()) {
                $this->reject('distribution', 'A distribution for this investment and job already exists.');
            }

            $allocation = $investment->allocations()->where('transport_job_id', $job->id)->where('status', AllocationStatus::Active)->first();
            if (! $allocation) {
                $this->reject('allocation', 'This investment has no active allocation on the job.');
            }

            $basis = round((float) $job->final_profit, 2);
            $value = $investment->return_type?->value === 'percentage'
                ? (float) $investment->return_percentage
                : (float) $investment->fixed_return_amount;
            $amount = $investment->calculated_return_amount;

            $distribution = InvestorProfitDistribution::create([
                'investment_id' => $investment->id, 'transport_job_id' => $job->id, 'investor_id' => $investment->investor_id,
                'allocation_id' => $allocation->id, 'profit_basis' => $basis, 'profit_share_value' => $value,
                'profit_amount' => $amount, 'status' => ProfitDistributionStatus::Confirmed, 'distributed_at' => now(), 'notes' => $notes,
            ]);

            $fundingInvestments = $job->allocations()->where('status', AllocationStatus::Active)->pluck('investment_id')->unique();
            $calculated = $job->profitDistributions()->whereIn('investment_id', $fundingInvestments)->count();
            if ($fundingInvestments->isNotEmpty() && $calculated === $fundingInvestments->count()) {
                $job->update(['financially_locked_at' => now()]);
            }

            return $distribution->load(['investor', 'investment', 'transportJob']);
        });
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
