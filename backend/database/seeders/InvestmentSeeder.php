<?php

namespace Database\Seeders;

use App\Enums\InvestmentCategory;
use App\Enums\InvestmentReturnType;
use App\Enums\InvestmentStatus;
use App\Models\Investment;
use App\Models\Investor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InvestmentSeeder extends Seeder
{
    private int $sequence = 0;

    public function run(): void
    {
        $investors = Investor::all();

        $statuses = array_merge(
            array_fill(0, 5, InvestmentStatus::Active),
            array_fill(0, 2, InvestmentStatus::Matured),
            array_fill(0, 1, InvestmentStatus::Withdrawn),
            array_fill(0, 1, InvestmentStatus::Settled),
            array_fill(0, 1, InvestmentStatus::Cancelled),
        );
        shuffle($statuses);

        foreach ($statuses as $status) {
            $this->sequence++;
            $investor = $investors->random();
            $amount = fake()->randomFloat(2, 100000, 2000000);
            $category = fake()->randomElement([InvestmentCategory::Pool, InvestmentCategory::Normal]);
            $returnType = fake()->randomElement([InvestmentReturnType::Percentage, InvestmentReturnType::Fixed]);
            $periodMonths = fake()->numberBetween(3, 24);
            $investmentDate = Carbon::parse(fake()->dateTimeBetween('-18 months', '-1 month'));

            $data = [
                'investment_code' => 'INVEST-'.str_pad((string) $this->sequence, 6, '0', STR_PAD_LEFT),
                'investor_id' => $investor->id,
                'investment_date' => $investmentDate->toDateString(),
                'amount' => $amount,
                'investment_category' => $category,
                'return_type' => $returnType,
                'return_percentage' => $returnType === InvestmentReturnType::Percentage ? fake()->randomFloat(2, 8, 22) : null,
                'fixed_return_amount' => $returnType === InvestmentReturnType::Fixed ? round($amount * fake()->randomFloat(2, 0.08, 0.18), 2) : null,
                'period_months' => $periodMonths,
                'return_policy_days' => fake()->numberBetween(30, 90),
                'status' => $status,
                'deduction_amount' => fake()->randomFloat(2, 0, $amount * 0.05),
                'notes' => fake()->optional(0.3)->sentence(),
            ];

            if ($status === InvestmentStatus::Matured || $status === InvestmentStatus::Settled || $status === InvestmentStatus::Withdrawn) {
                $data['matured_at'] = $investmentDate->copy()->addMonths($periodMonths);
            }

            if ($status === InvestmentStatus::Withdrawn) {
                $data['withdrawn_at'] = $data['matured_at']?->copy()->addDays(fake()->numberBetween(1, 30));
            }

            if ($status === InvestmentStatus::Settled) {
                $data['settled_at'] = $data['matured_at']?->copy()->addDays(fake()->numberBetween(1, 30));
            }

            if ($status === InvestmentStatus::Cancelled) {
                $data['cancelled_at'] = $investmentDate->copy()->addDays(fake()->numberBetween(7, 60));
            }

            Investment::create($data);
        }
    }
}
