<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Investor;
use App\Models\TransportJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyProfitCapitalTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_profit_can_be_added_to_available_capital_and_is_consumed_once(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->initializeCapital(10000);
        $this->jobWithProfit(5000);

        $this->getJson('/api/v1/company-capital/profit')
            ->assertOk()
            ->assertJsonPath('data.total_profit', 5000)
            ->assertJsonPath('data.transferred_profit', 0)
            ->assertJsonPath('data.available_profit', 5000);

        $this->postJson('/api/v1/company-capital/profit/add', [
            'amount' => 3000,
            'transaction_date' => today()->toDateString(),
        ])->assertOk();

        $this->getJson('/api/v1/company-capital')
            ->assertJsonPath('data.available_to_lend', 13000)
            ->assertJsonPath('data.total_capital', 13000);

        $this->getJson('/api/v1/company-capital/profit')
            ->assertJsonPath('data.available_profit', 2000)
            ->assertJsonPath('data.transferred_profit', 3000);

        $this->postJson('/api/v1/company-capital/profit/add', [
            'amount' => 2000,
            'transaction_date' => today()->toDateString(),
        ])->assertOk();

        $this->getJson('/api/v1/company-capital')
            ->assertJsonPath('data.available_to_lend', 15000)
            ->assertJsonPath('data.total_capital', 15000);

        $this->getJson('/api/v1/company-capital/profit')
            ->assertJsonPath('data.available_profit', 0)
            ->assertJsonPath('data.transferred_profit', 5000);
    }

    public function test_profit_transfer_cannot_exceed_undistributed_profit(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->initializeCapital(10000);
        $this->jobWithProfit(5000);

        $this->postJson('/api/v1/company-capital/profit/add', [
            'amount' => 5001,
            'transaction_date' => today()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('amount');
    }

    public function test_profit_transfer_increases_loan_eligibility_without_changing_loan_rules(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->initializeCapital(10000);
        $this->jobWithProfit(5000);

        $investor = Investor::create([
            'investor_code' => 'INV-'.str_pad((string) (Investor::count() + 1), 6, '0', STR_PAD_LEFT),
            'name' => 'Profit Capital Investor',
            'email' => 'profit-capital@example.com',
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/loans', [
            'borrower_type' => 'investor',
            'investor_id' => $investor->id,
            'amount' => 12000,
            'loan_date' => today()->toDateString(),
            'due_date' => today()->addMonth()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('capital');

        $this->postJson('/api/v1/company-capital/profit/add', [
            'amount' => 5000,
            'transaction_date' => today()->toDateString(),
        ])->assertOk();

        $this->postJson('/api/v1/loans', [
            'borrower_type' => 'investor',
            'investor_id' => $investor->id,
            'amount' => 12000,
            'loan_date' => today()->toDateString(),
            'due_date' => today()->addMonth()->toDateString(),
        ])->assertOk();
    }

    private function initializeCapital(float $amount): void
    {
        $this->postJson('/api/v1/company-capital/initialize', [
            'amount' => $amount,
            'transaction_date' => today()->toDateString(),
        ])->assertOk();
    }

    private function jobWithProfit(float $profit): TransportJob
    {
        $customer = Customer::create([
            'code' => 'CUS-'.uniqid(),
            'name' => 'Profit Capital Customer',
        ]);

        $estimate = Estimate::create([
            'code' => 'EST-'.uniqid(),
            'customer_id' => $customer->id,
            'estimate_date' => today(),
            'pickup' => 'Karachi',
            'destination' => 'Lahore',
            'service_type' => 'goods',
            'status' => 'accepted',
            'estimated_sell' => $profit,
        ]);

        return TransportJob::create([
            'code' => 'JOB-'.uniqid(),
            'estimate_id' => $estimate->id,
            'customer_id' => $customer->id,
            'job_date' => today(),
            'status' => JobStatus::Completed,
            'sell_price' => $profit,
            'cost_price' => 0,
            'base_profit' => $profit,
            'extra_costs' => 0,
            'final_profit' => $profit,
        ]);
    }
}
