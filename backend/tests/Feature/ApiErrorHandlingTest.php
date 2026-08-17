<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\EstimateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The API answers in JSON with a sensible status whatever the caller asks for.
 * A 500 here would leak a stack trace for something that is not a fault.
 */
class ApiErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unauthenticated_request_is_401_even_without_an_accept_header(): void
    {
        // The default guest redirect resolves route('login'), which does not
        // exist in an API-only app — that used to throw and render as a 500.
        $response = $this->get('/api/v1/jobs');

        $response->assertStatus(401);
        $this->assertSame('Unauthenticated.', $response->json('message'));
    }

    public function test_an_unauthenticated_json_request_is_also_401(): void
    {
        $this->getJson('/api/v1/jobs')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_a_missing_record_is_404_rather_than_a_crash(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/jobs/999999')->assertStatus(404);
        $this->getJson('/api/v1/jobs/not-an-id')->assertStatus(404);
    }

    public function test_converting_the_same_estimate_twice_is_refused_as_a_rule_not_a_fault(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $customer = Customer::create(['code' => 'CUS-000001', 'name' => 'Acme Logistics']);

        $estimate = app(EstimateService::class)->create([
            'customer_id' => $customer->id,
            'estimate_date' => '2026-08-14',
            'pickup' => 'Karachi',
            'destination' => 'Lahore',
            'service_type' => 'goods',
            'items' => [
                ['title' => 'Transportation', 'category' => 'Transport', 'quantity' => 1, 'cost_price' => 100, 'sell_price' => 200],
            ],
        ]);

        // 201 — Laravel answers created for a resource wrapping a new model.
        $this->postJson("/api/v1/estimates/{$estimate->id}/convert")->assertCreated();

        $this->postJson("/api/v1/estimates/{$estimate->id}/convert")
            ->assertStatus(422)
            ->assertJsonPath('message', 'This estimate has already been converted to a job.');
    }
}
