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

class TransportJobNotesTest extends TestCase
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

    public function test_notes_are_saved_and_read_back(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        $this->patchJson("/api/v1/jobs/{$job->id}/notes", [
            'internal_notes' => 'Driver says the crane will be an hour late.',
        ])
            ->assertOk()
            ->assertJsonPath('data.internal_notes', 'Driver says the crane will be an hour late.');

        $this->getJson("/api/v1/jobs/{$job->id}")
            ->assertOk()
            ->assertJsonPath('data.internal_notes', 'Driver says the crane will be an hour late.');
    }

    public function test_an_empty_textarea_clears_the_notes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();
        $service = app(TransportJobService::class);

        $service->updateNotes($job, 'something');

        $this->patchJson("/api/v1/jobs/{$job->id}/notes", ['internal_notes' => '   '])
            ->assertOk()
            ->assertJsonPath('data.internal_notes', null);

        $this->assertNull($job->fresh()->internal_notes);
    }

    public function test_notes_do_not_leak_into_the_estimate_response(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $job = $this->job();

        app(TransportJobService::class)->updateNotes($job, 'Never show this to the customer.');

        // GET /estimates eager-loads the job, so this is where the field would
        // escape if it were not hidden on the model.
        $response = $this->getJson('/api/v1/estimates')->assertOk();

        $this->assertStringNotContainsString('internal_notes', $response->getContent());
        $this->assertStringNotContainsString('Never show this to the customer.', $response->getContent());
    }

    public function test_the_endpoint_is_behind_authentication(): void
    {
        $job = $this->job();

        $this->patchJson("/api/v1/jobs/{$job->id}/notes", ['internal_notes' => 'nope'])
            ->assertStatus(401);

        $this->assertNull($job->fresh()->internal_notes);
    }
}
