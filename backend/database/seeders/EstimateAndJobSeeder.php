<?php

namespace Database\Seeders;

use App\Enums\ActivityEvent;
use App\Enums\ExpenseCategory;
use App\Enums\JobStatus;
use App\Models\Asset;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\EstimateItemVehicle;
use App\Models\TransportJob;
use App\Models\TransportJobActivity;
use App\Models\TransportJobExpense;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EstimateAndJobSeeder extends Seeder
{
    private int $estimateSequence = 0;

    private int $jobSequence = 0;

    public function run(): void
    {
        $customers = Customer::all();
        $assets = Asset::all();
        $user = User::first();

        $routes = [
            ['pickup' => 'Karachi', 'destination' => 'Lahore'],
            ['pickup' => 'Lahore', 'destination' => 'Islamabad'],
            ['pickup' => 'Karachi', 'destination' => 'Hyderabad'],
            ['pickup' => 'Faisalabad', 'destination' => 'Multan'],
            ['pickup' => 'Rawalpindi', 'destination' => 'Peshawar'],
            ['pickup' => 'Quetta', 'destination' => 'Karachi'],
        ];

        // Pending estimates (draft / sent) for dashboard alerts
        foreach (range(1, 5) as $i) {
            $this->createEstimate($customers->random(), $assets, $routes, 'draft', true);
        }
        foreach (range(1, 4) as $i) {
            $this->createEstimate($customers->random(), $assets, $routes, 'sent', true);
        }

        // Accepted estimates that will become jobs
        $statuses = array_merge(
            array_fill(0, 2, JobStatus::Draft),
            array_fill(0, 3, JobStatus::Confirmed),
            array_fill(0, 3, JobStatus::Assigned),
            array_fill(0, 3, JobStatus::InTransit),
            array_fill(0, 3, JobStatus::Delivered),
            array_fill(0, 6, JobStatus::Completed),
        );
        shuffle($statuses);

        foreach ($statuses as $status) {
            $estimate = $this->createEstimate($customers->random(), $assets, $routes, 'accepted', false);
            $this->createJobFromEstimate($estimate, $status, $assets, $user);
        }
    }

    private function createEstimate(Customer $customer, $assets, array $routes, string $status, bool $pending): Estimate
    {
        $this->estimateSequence++;
        $route = fake()->randomElement($routes);
        $estimateDate = $pending
            ? Carbon::parse(fake()->dateTimeBetween('-10 days', 'today'))
            : Carbon::parse(fake()->dateTimeBetween('-3 months', '-5 days'));

        $validUntil = $pending
            ? $estimateDate->copy()->addDays(fake()->numberBetween(5, 30))
            : $estimateDate->copy()->addDays(fake()->numberBetween(7, 21));

        $items = [];
        $itemCount = fake()->numberBetween(1, 3);
        $estimatedCost = 0;
        $estimatedSell = 0;

        for ($i = 1; $i <= $itemCount; $i++) {
            $quantity = fake()->numberBetween(1, 3);
            $costPrice = fake()->randomFloat(2, 20000, 120000);
            $sellPrice = $costPrice * fake()->randomFloat(2, 1.15, 1.45);
            $costTotal = round($costPrice * $quantity, 2);
            $sellTotal = round($sellPrice * $quantity, 2);
            $profit = round($sellTotal - $costTotal, 2);

            $items[] = [
                'title' => fake()->randomElement(['Transportation', 'Freight', 'Loading', 'Insurance', 'Toll', 'Documentation']),
                'category' => fake()->randomElement(['logistics', 'transport', 'handling']),
                'quantity' => $quantity,
                'cost_price' => $costPrice,
                'sell_price' => $sellPrice,
                'cost_total' => $costTotal,
                'sell_total' => $sellTotal,
                'profit' => $profit,
            ];

            $estimatedCost += $costTotal;
            $estimatedSell += $sellTotal;
        }

        $estimate = Estimate::create([
            'code' => 'EST-'.str_pad((string) $this->estimateSequence, 6, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'estimate_date' => $estimateDate->toDateString(),
            'valid_until' => $validUntil->toDateString(),
            'pickup' => $route['pickup'],
            'destination' => $route['destination'],
            'service_type' => fake()->randomElement(['goods', 'vehicle']),
            'estimated_cost' => round($estimatedCost, 2),
            'estimated_sell' => round($estimatedSell, 2),
            'estimated_profit' => round($estimatedSell - $estimatedCost, 2),
            'status' => $status,
            'remarks' => fake()->optional(0.3)->sentence(),
        ]);

        foreach ($items as $itemData) {
            $item = EstimateItem::create(array_merge($itemData, ['estimate_id' => $estimate->id]));

            if ($estimate->service_type === 'vehicle' || fake()->boolean(60)) {
                $asset = $assets->random();
                EstimateItemVehicle::create([
                    'estimate_item_id' => $item->id,
                    'source' => fake()->randomElement(['company', 'company', 'hired']),
                    'asset_id' => $asset->id,
                    'vehicle_name' => $asset->name,
                    'make' => $asset->make,
                    'model' => $asset->model,
                    'model_year' => $asset->model_year,
                    'registration_number' => $asset->registration_number,
                    'vin' => $asset->vin,
                    'engine_number' => $asset->engine_number,
                    'vehicle_type' => $asset->vehicle_type,
                    'color' => $asset->color,
                    'notes' => fake()->optional(0.3)->sentence(),
                ]);
            }
        }

        return $estimate;
    }

    private function createJobFromEstimate(Estimate $estimate, JobStatus $status, $assets, ?User $user): void
    {
        $this->jobSequence++;

        $jobDate = Carbon::parse($estimate->estimate_date)->addDays(fake()->numberBetween(1, 5));
        if ($jobDate->isFuture()) {
            $jobDate = Carbon::parse(fake()->dateTimeBetween('-3 months', 'yesterday'));
        }

        $costPrice = $estimate->estimated_cost;
        $sellPrice = $estimate->estimated_sell;
        $baseProfit = round($sellPrice - $costPrice, 2);

        $job = TransportJob::create([
            'code' => 'JOB-'.str_pad((string) $this->jobSequence, 6, '0', STR_PAD_LEFT),
            'estimate_id' => $estimate->id,
            'customer_id' => $estimate->customer_id,
            'job_date' => $jobDate->toDateString(),
            'status' => $status,
            'sell_price' => $sellPrice,
            'cost_price' => $costPrice,
            'base_profit' => $baseProfit,
            'extra_costs' => 0,
            'final_profit' => $baseProfit,
            'remarks' => fake()->optional(0.3)->sentence(),
            'internal_notes' => fake()->optional(0.2)->sentence(),
        ]);

        TransportJobActivity::create([
            'job_id' => $job->id,
            'event_type' => ActivityEvent::JobCreated,
            'description' => "Job {$job->code} created from estimate {$estimate->code}.",
            'old_value' => null,
            'new_value' => ['status' => $status->value],
            'created_by' => $user?->id,
            'created_at' => $jobDate->copy()->setTime(9, 0),
        ]);

        if (fake()->boolean(70)) {
            $this->addExpenses($job, $jobDate, $baseProfit, $user);
        }

        $job->recalculate();
    }

    private function addExpenses(TransportJob $job, Carbon $jobDate, float $baseProfit, ?User $user): void
    {
        $expenseCount = fake()->numberBetween(0, 3);
        $categories = ExpenseCategory::cases();

        for ($i = 0; $i < $expenseCount; $i++) {
            // Keep expenses reasonable relative to job profit
            $maxAmount = max(500, $baseProfit * 0.25);
            $amount = fake()->randomFloat(2, 500, $maxAmount);

            TransportJobExpense::create([
                'job_id' => $job->id,
                'title' => fake()->randomElement(['Fuel', 'Toll', 'Repair', 'Parking', 'Driver allowance', 'Loading']),
                'category' => fake()->randomElement($categories),
                'amount' => $amount,
                'expense_date' => $jobDate->copy()->addDays(fake()->numberBetween(0, 3))->toDateString(),
                'notes' => fake()->optional(0.3)->sentence(),
            ]);
        }
    }
}
