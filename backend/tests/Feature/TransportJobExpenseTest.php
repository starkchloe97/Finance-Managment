<?php

namespace Tests\Feature;

use App\Enums\ActivityEvent;
use App\Models\Customer;
use App\Models\TransportJob;
use App\Models\TransportJobActivity;
use App\Models\TransportJobExpense;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\EstimateService;
use App\Services\TransportJobExpenseService;
use App\Services\TransportJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class TransportJobExpenseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The money columns on transport_jobs are not cast, so they arrive as
     * strings on MySQL and as numbers on the sqlite the suite runs against.
     * Compare the value, not the spelling.
     */
    private function assertMoney(float $expected, $actual): void
    {
        $this->assertSame($expected, (float) $actual);
    }

    /**
     * Defaults to cost 45,000 / sell 50,000 -> base profit 5,000.
     */
    private function job(float $cost = 45000, float $sell = 50000): TransportJob
    {
        $customer = Customer::create([
            'code' => 'CUS-'.str_pad((string) (Customer::count() + 1), 6, '0', STR_PAD_LEFT),
            'name' => 'Acme Logistics',
        ]);

        $estimate = app(EstimateService::class)->create([
            'customer_id' => $customer->id,
            'estimate_date' => '2026-08-14',
            'pickup' => 'Karachi',
            'destination' => 'Lahore',
            'service_type' => 'goods',
            'items' => [
                ['title' => 'Transportation', 'category' => 'Transport', 'quantity' => 1, 'cost_price' => $cost, 'sell_price' => $sell],
            ],
        ]);

        return app(TransportJobService::class)->convert($estimate);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Truck repair',
            'category' => 'repair',
            'amount' => 5000,
            'expense_date' => '2026-08-15',
            'notes' => null,
        ], $overrides);
    }

    /**
     * The whole chain in one pass: sell − cost = base profit, then every
     * mutation moves extra_costs and final_profit and nothing else. The agreed
     * figures are re-checked at each step because they are what must not move.
     */
    public function test_the_financial_chain_holds_through_every_mutation(): void
    {
        $job = $this->job(cost: 30000, sell: 50000);
        $costs = app(TransportJobExpenseService::class);

        $agreed = function () use ($job) {
            $this->assertMoney(50000.0, $job->fresh()->sell_price);
            $this->assertMoney(30000.0, $job->fresh()->cost_price);
            $this->assertMoney(20000.0, $job->fresh()->base_profit);
        };

        // No expenses yet.
        $agreed();
        $this->assertMoney(0.0, $job->fresh()->extra_costs);
        $this->assertMoney(20000.0, $job->fresh()->final_profit);

        // One expense.
        $first = $costs->add($job, $this->payload(['amount' => 5000]));
        $agreed();
        $this->assertMoney(5000.0, $job->fresh()->extra_costs);
        $this->assertMoney(15000.0, $job->fresh()->final_profit);

        // Several expenses accumulate.
        $costs->add($job->fresh(), $this->payload(['amount' => 3000, 'category' => 'fuel']));
        $costs->add($job->fresh(), $this->payload(['amount' => 2000, 'category' => 'toll']));
        $agreed();
        $this->assertMoney(10000.0, $job->fresh()->extra_costs);
        $this->assertMoney(10000.0, $job->fresh()->final_profit);

        // Editing one moves the totals by the difference.
        $costs->update($first, $this->payload(['amount' => 8000]));
        $agreed();
        $this->assertMoney(13000.0, $job->fresh()->extra_costs);
        $this->assertMoney(7000.0, $job->fresh()->final_profit);

        // Deleting it gives the profit back.
        $costs->remove($first->fresh());
        $agreed();
        $this->assertMoney(5000.0, $job->fresh()->extra_costs);
        $this->assertMoney(15000.0, $job->fresh()->final_profit);

        // Past the base profit the job runs at a real loss, recorded as one.
        $costs->add($job->fresh(), $this->payload(['amount' => 25000, 'category' => 'repair']));
        $agreed();
        $this->assertMoney(30000.0, $job->fresh()->extra_costs);
        $this->assertMoney(-10000.0, $job->fresh()->final_profit);
    }

    public function test_extra_costs_ignore_anything_the_client_sends(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        // extra_costs is summed from the rows, never taken from the request.
        $this->postJson("/api/v1/jobs/{$job->id}/expenses", $this->payload([
            'amount' => 1000,
            'extra_costs' => 999999,
            'final_profit' => 999999,
            'base_profit' => 999999,
        ]))->assertOk();

        $this->assertMoney(1000.0, $job->fresh()->extra_costs);
        $this->assertMoney(5000.0, $job->fresh()->base_profit);
        $this->assertMoney(4000.0, $job->fresh()->final_profit);
    }

    public function test_adding_a_cost_returns_the_recalculated_job(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $response = $this->postJson("/api/v1/jobs/{$job->id}/expenses", $this->payload())->assertOk();

        // The response carries the recalculated figures, so the caller never
        // has to work them out or ask again.
        $this->assertMoney(5000.0, $response->json('data.base_profit'));
        $this->assertMoney(5000.0, $response->json('data.extra_costs'));
        $this->assertMoney(0.0, $response->json('data.final_profit'));
    }

    public function test_editing_a_cost_moves_the_totals(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $expense = app(TransportJobExpenseService::class)->add($job, $this->payload(['amount' => 1000]));

        $response = $this->patchJson(
            "/api/v1/jobs/{$job->id}/expenses/{$expense->id}",
            $this->payload(['amount' => 4000])
        )->assertOk();

        $this->assertMoney(4000.0, $response->json('data.extra_costs'));
        $this->assertMoney(1000.0, $response->json('data.final_profit'));

        $this->assertSame('4000.00', $expense->fresh()->amount);
    }

    public function test_deleting_a_cost_restores_the_profit(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $expense = app(TransportJobExpenseService::class)->add($job, $this->payload());

        $response = $this->deleteJson("/api/v1/jobs/{$job->id}/expenses/{$expense->id}")->assertOk();

        $this->assertMoney(0.0, $response->json('data.extra_costs'));
        $this->assertMoney(5000.0, $response->json('data.final_profit'));

        $this->assertSame(0, TransportJobExpense::count());
    }

    public function test_a_negative_amount_is_refused(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $this->postJson("/api/v1/jobs/{$job->id}/expenses", $this->payload(['amount' => -500]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');

        $this->assertMoney(0.0, $job->fresh()->extra_costs);
    }

    public function test_a_zero_amount_is_refused(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        // Zero is not a cost. Accepting it would put a line on the job that
        // moves nothing.
        $this->postJson("/api/v1/jobs/{$job->id}/expenses", $this->payload(['amount' => 0]))
            ->assertStatus(422)
            ->assertJsonPath('errors.amount.0', 'Amount must be greater than 0.');

        $this->assertSame(0, TransportJobExpense::count());
    }

    public function test_every_required_field_is_enforced(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $this->postJson("/api/v1/jobs/{$job->id}/expenses", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'category', 'amount', 'expense_date']);

        // Notes are the only optional field.
        $this->postJson("/api/v1/jobs/{$job->id}/expenses", $this->payload(['notes' => null]))
            ->assertOk();
    }

    public function test_a_title_longer_than_the_column_is_refused(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $this->postJson("/api/v1/jobs/{$job->id}/expenses", $this->payload(['title' => str_repeat('a', 256)]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_a_category_outside_the_list_is_refused(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $this->postJson("/api/v1/jobs/{$job->id}/expenses", $this->payload(['category' => 'Breakdown']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('category');
    }

    public function test_the_same_rules_apply_when_editing(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $expense = app(TransportJobExpenseService::class)->add($job, $this->payload());

        $this->patchJson(
            "/api/v1/jobs/{$job->id}/expenses/{$expense->id}",
            $this->payload(['amount' => -1, 'category' => 'nonsense', 'title' => ''])
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'category', 'title']);

        // The rejected edit must not have touched the figures.
        $this->assertSame('5000.00', $expense->fresh()->amount);
        $this->assertMoney(5000.0, $job->fresh()->extra_costs);
    }

    public function test_a_cost_cannot_be_reached_through_another_job(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();
        $other = $this->job();

        $expense = app(TransportJobExpenseService::class)->add($job, $this->payload());

        $this->patchJson("/api/v1/jobs/{$other->id}/expenses/{$expense->id}", $this->payload(['amount' => 1]))
            ->assertStatus(404);

        $this->deleteJson("/api/v1/jobs/{$other->id}/expenses/{$expense->id}")
            ->assertStatus(404);

        $this->assertSame('5000.00', $expense->fresh()->amount);
    }

    public function test_the_endpoints_are_behind_authentication(): void
    {
        $job = $this->job();

        $expense = app(TransportJobExpenseService::class)->add($job, $this->payload());

        $this->postJson("/api/v1/jobs/{$job->id}/expenses", $this->payload())->assertStatus(401);
        $this->patchJson("/api/v1/jobs/{$job->id}/expenses/{$expense->id}", $this->payload())->assertStatus(401);
        $this->deleteJson("/api/v1/jobs/{$job->id}/expenses/{$expense->id}")->assertStatus(401);
    }

    public function test_editing_a_cost_is_recorded_with_both_sides(): void
    {
        $job = $this->job();

        $expense = app(TransportJobExpenseService::class)->add($job, $this->payload(['amount' => 15000]));

        app(TransportJobExpenseService::class)->update($expense, $this->payload([
            'amount' => 18000,
            'category' => 'fuel',
        ]));

        $activity = $job->activities()->where('event_type', 'cost_updated')->sole();

        $this->assertSame(
            'Unexpected cost updated: Truck repair (15,000.00 → 18,000.00)',
            $activity->description
        );

        $this->assertSame('15000.00', $activity->old_value['amount']);
        $this->assertSame('repair', $activity->old_value['category']);

        $this->assertSame('18000.00', $activity->new_value['amount']);
        $this->assertSame('fuel', $activity->new_value['category']);
    }

    public function test_an_edit_that_leaves_the_amount_alone_says_so(): void
    {
        $job = $this->job();

        $expense = app(TransportJobExpenseService::class)->add($job, $this->payload());

        app(TransportJobExpenseService::class)->update($expense, $this->payload(['notes' => 'Paid in cash.']));

        $activity = $job->activities()->where('event_type', 'cost_updated')->sole();

        $this->assertSame('Unexpected cost updated: Truck repair', $activity->description);
        $this->assertSame('Paid in cash.', $activity->new_value['notes']);
    }

    public function test_a_rejected_edit_leaves_no_activity(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $expense = app(TransportJobExpenseService::class)->add($job, $this->payload());

        $this->patchJson("/api/v1/jobs/{$job->id}/expenses/{$expense->id}", $this->payload(['amount' => -1]))
            ->assertStatus(422);

        $this->assertSame(0, $job->activities()->where('event_type', 'cost_updated')->count());
    }

    public function test_a_failed_mutation_rolls_back_the_whole_operation(): void
    {
        $job = $this->job();

        app(TransportJobExpenseService::class)->add($job, $this->payload(['amount' => 3000]));

        // The expense write, the recalculation and the timeline entry share one
        // transaction. Making the last of the three fail is how the boundary
        // gets proven: nothing before it may survive.
        $this->app->instance(ActivityService::class, new class extends ActivityService
        {
            public function log(TransportJob $job, ActivityEvent $event, string $description, ?array $old = null, ?array $new = null): TransportJobActivity
            {
                throw new RuntimeException('activity logging failed');
            }
        });

        try {
            app(TransportJobExpenseService::class)->add($job->fresh(), $this->payload(['amount' => 9999]));
            $this->fail('The mutation should have thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('activity logging failed', $e->getMessage());
        }

        // The second expense must not exist, and the totals must still describe
        // only the first one.
        $this->assertSame(1, TransportJobExpense::count());
        $this->assertMoney(3000.0, $job->fresh()->extra_costs);
        $this->assertMoney(2000.0, $job->fresh()->final_profit);
    }

    public function test_expenses_are_not_orphaned_when_a_job_goes(): void
    {
        $job = $this->job();

        app(TransportJobExpenseService::class)->add($job, $this->payload());

        // A job is soft-deleted, so its expenses still belong to a row that is
        // there — they are hidden with it rather than orphaned.
        $job->delete();

        $this->assertSame(1, TransportJobExpense::count());
        $this->assertSame($job->id, TransportJobExpense::sole()->job_id);
        $this->assertNotNull(TransportJob::withTrashed()->find($job->id));

        // Only a hard delete removes the row for real, and the FK cascade takes
        // the expenses with it.
        $job->forceDelete();

        $this->assertSame(0, TransportJobExpense::count());
        $this->assertSame(0, $job->activities()->count());
    }

    public function test_the_job_response_already_carries_its_costs(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        app(TransportJobExpenseService::class)->add($job, $this->payload());

        // No separate list endpoint exists because this one already nests them.
        $this->getJson("/api/v1/jobs/{$job->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.expenses')
            ->assertJsonPath('data.expenses.0.category', 'repair');
    }
}
