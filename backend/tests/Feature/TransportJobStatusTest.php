<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\TransportJob;
use App\Models\User;
use App\Services\EstimateService;
use App\Services\TransportJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransportJobStatusTest extends TestCase
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

    public function test_a_new_job_starts_as_a_draft(): void
    {
        $this->assertSame('draft', $this->job()->status->value);
    }

    public function test_a_job_can_be_walked_forward_one_stage_at_a_time(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        foreach (['confirmed', 'assigned', 'in_transit', 'delivered', 'completed'] as $stage) {
            $this->patchJson("/api/v1/jobs/{$job->id}/status", ['status' => $stage])
                ->assertOk()
                ->assertJsonPath('data.status', $stage);
        }

        $this->assertSame('completed', $job->fresh()->status->value);
    }

    public function test_a_stage_cannot_be_skipped(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $this->patchJson("/api/v1/jobs/{$job->id}/status", ['status' => 'delivered'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot transition from draft to delivered.');

        $this->assertSame('draft', $job->fresh()->status->value);
    }

    public function test_a_job_cannot_be_moved_backwards(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        app(TransportJobService::class)->changeStatus($job, 'confirmed');

        $this->patchJson("/api/v1/jobs/{$job->id}/status", ['status' => 'draft'])
            ->assertStatus(422);

        $this->assertSame('confirmed', $job->fresh()->status->value);
    }

    public function test_completed_is_the_end_of_the_road(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();
        $service = app(TransportJobService::class);

        foreach (['confirmed', 'assigned', 'in_transit', 'delivered', 'completed'] as $stage) {
            $service->changeStatus($job->fresh(), $stage);
        }

        foreach (['draft', 'confirmed', 'assigned', 'in_transit', 'delivered', 'completed'] as $stage) {
            $this->patchJson("/api/v1/jobs/{$job->id}/status", ['status' => $stage])
                ->assertStatus(422);
        }

        $this->assertSame('completed', $job->fresh()->status->value);
    }

    public function test_a_status_that_is_not_a_stage_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $this->patchJson("/api/v1/jobs/{$job->id}/status", ['status' => 'cancelled'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_the_endpoint_is_behind_authentication(): void
    {
        $job = $this->job();

        $this->patchJson("/api/v1/jobs/{$job->id}/status", ['status' => 'confirmed'])
            ->assertStatus(401);

        $this->assertSame('draft', $job->fresh()->status->value);
    }
}
