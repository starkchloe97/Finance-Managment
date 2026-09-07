<?php

namespace Database\Seeders;

use App\Enums\AllocationStatus;
use App\Enums\ProfitDistributionStatus;
use App\Models\Investment;
use App\Models\InvestmentAllocation;
use App\Models\InvestorProfitDistribution;
use App\Models\TransportJob;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InvestmentAllocationSeeder extends Seeder
{
    public function run(): void
    {
        $activeInvestments = Investment::where('status', 'active')->get();
        $completedJobs = TransportJob::whereIn('status', ['delivered', 'completed'])->get();

        if ($activeInvestments->isEmpty() || $completedJobs->isEmpty()) {
            return;
        }

        $allocationCount = min(8, $completedJobs->count());
        $selectedJobs = $completedJobs->shuffle()->take($allocationCount);

        foreach ($selectedJobs as $job) {
            $investment = $activeInvestments->shuffle()->first(function (Investment $investment) {
                return $investment->remaining_capital > 50000;
            }) ?? $activeInvestments->random();

            $maxAllocation = min($investment->remaining_capital, $job->sell_price * 0.5, 500000);
            if ($maxAllocation < 25000) {
                continue;
            }

            $amount = fake()->randomFloat(2, 25000, $maxAllocation);
            $status = fake()->randomElement([AllocationStatus::Active, AllocationStatus::Active, AllocationStatus::Released]);

            $allocation = InvestmentAllocation::create([
                'investment_id' => $investment->id,
                'transport_job_id' => $job->id,
                'amount' => $amount,
                'status' => $status,
                'allocated_at' => Carbon::parse($job->job_date)->addDays(fake()->numberBetween(1, 5)),
                'notes' => fake()->optional(0.3)->sentence(),
            ]);

            if ($status === AllocationStatus::Active && fake()->boolean(60)) {
                $profitBasis = max(0, $job->final_profit);
                $share = fake()->randomFloat(4, 0.05, 0.25);
                $profitAmount = round($profitBasis * $share, 2);

                InvestorProfitDistribution::create([
                    'investment_id' => $investment->id,
                    'transport_job_id' => $job->id,
                    'investor_id' => $investment->investor_id,
                    'allocation_id' => $allocation->id,
                    'profit_basis' => $profitBasis,
                    'profit_share_value' => $share,
                    'profit_amount' => $profitAmount,
                    'status' => ProfitDistributionStatus::Confirmed,
                    'distributed_at' => Carbon::parse($job->job_date)->addDays(fake()->numberBetween(10, 30)),
                    'notes' => fake()->optional(0.3)->sentence(),
                ]);
            }
        }
    }
}
