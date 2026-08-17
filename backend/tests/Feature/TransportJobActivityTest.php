<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\TransportJob;
use App\Models\User;
use App\Services\EstimateService;
use App\Services\TransportJobExpenseService;
use App\Services\TransportJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransportJobActivityTest extends TestCase
{
    use RefreshDatabase;

    private function job(): TransportJob
    {
        $customer = Customer::create([
            'code' => 'CUS-000001',
            'name' => 'Acme Logistics',
        ]);

        $estimate = app(EstimateService::class)->create([
            'customer_id' => $customer->id,
            'estimate_date' => '2026-08-14',
            'pickup' => 'Karachi',
            'destination' => 'Lahore',
            'service_type' => 'goods',
            'items' => [
                ['title' => 'Transportation', 'category' => 'Transport', 'quantity' => 1, 'cost_price' => 45000, 'sell_price' => 50000],
            ],
        ]);

        return app(TransportJobService::class)->convert($estimate);
    }

    public function test_creating_a_job_opens_its_timeline(): void
    {
        $job = $this->job();

        $activity = $job->activities()->sole();

        $this->assertSame('job_created', $activity->event_type->value);
        $this->assertNull($activity->old_value);
        $this->assertSame($job->code, $activity->new_value['code']);
    }

    public function test_every_kind_of_event_is_recorded(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->actingAs($user);

        $job = $this->job();

        app(TransportJobService::class)->changeStatus($job, 'confirmed');

        app(TransportJobService::class)->updateNotes($job->fresh(), 'Crane running late.');

        $expense = app(TransportJobExpenseService::class)->add($job->fresh(), [
            'title' => 'Truck repair',
            'category' => 'repair',
            'amount' => 5000,
            'expense_date' => '2026-08-15',
        ]);

        app(TransportJobExpenseService::class)->remove($expense);

        $events = $job->activities()->orderBy('id')->pluck('event_type')
            ->map(fn ($event) => $event->value)
            ->all();

        $this->assertSame(
            ['job_created', 'status_changed', 'notes_updated', 'cost_added', 'cost_deleted'],
            $events
        );

        // Everything done through a signed-in request is attributed to them.
        $this->assertSame(
            [$user->id, $user->id, $user->id, $user->id],
            $job->activities()->orderBy('id')->skip(1)->take(4)->pluck('created_by')->all()
        );
    }

    public function test_a_status_change_records_both_sides(): void
    {
        $job = $this->job();

        app(TransportJobService::class)->changeStatus($job, 'confirmed');

        $activity = $job->activities()->where('event_type', 'status_changed')->sole();

        $this->assertSame('Status moved from draft to confirmed', $activity->description);
        $this->assertSame(['status' => 'draft'], $activity->old_value);
        $this->assertSame(['status' => 'confirmed'], $activity->new_value);
    }

    public function test_a_rejected_status_change_leaves_no_trace(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $this->patchJson("/api/v1/jobs/{$job->id}/status", ['status' => 'delivered'])
            ->assertStatus(422);

        $this->assertSame(1, $job->activities()->count());
    }

    public function test_notes_saved_unchanged_are_not_logged_twice(): void
    {
        $job = $this->job();
        $service = app(TransportJobService::class);

        $service->updateNotes($job, 'Same note.');
        $service->updateNotes($job->fresh(), 'Same note.');

        $this->assertSame(1, $job->activities()->where('event_type', 'notes_updated')->count());
    }

    public function test_a_removed_cost_is_still_described_after_it_is_gone(): void
    {
        $job = $this->job();

        $expense = app(TransportJobExpenseService::class)->add($job, [
            'title' => 'Truck repair',
            'category' => 'repair',
            'amount' => 5000,
            'expense_date' => '2026-08-15',
        ]);

        app(TransportJobExpenseService::class)->remove($expense);

        $activity = $job->activities()->where('event_type', 'cost_deleted')->sole();

        $this->assertSame('Unexpected cost removed: Truck repair (5,000.00)', $activity->description);
        $this->assertSame('Truck repair', $activity->old_value['title']);
        $this->assertNull($activity->new_value);
    }

    public function test_the_timeline_endpoint_returns_newest_first_with_the_author(): void
    {
        $user = User::factory()->create(['name' => 'Ather']);
        Sanctum::actingAs($user);
        $this->actingAs($user);

        $job = $this->job();

        app(TransportJobService::class)->changeStatus($job, 'confirmed');

        $response = $this->getJson("/api/v1/jobs/{$job->id}/activities")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $response->assertJsonPath('data.0.event_type', 'status_changed');
        $response->assertJsonPath('data.0.author', 'Ather');
        $response->assertJsonPath('data.1.event_type', 'job_created');
    }

    public function test_the_timeline_is_behind_authentication(): void
    {
        $job = $this->job();

        $this->getJson("/api/v1/jobs/{$job->id}/activities")->assertStatus(401);
    }
}
