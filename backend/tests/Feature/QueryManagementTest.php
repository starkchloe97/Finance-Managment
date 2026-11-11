<?php

namespace Tests\Feature;

use App\Models\Query;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QueryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_management_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/queries')->assertUnauthorized();
        $this->postJson('/api/v1/queries', [])->assertUnauthorized();
        $this->getJson('/api/v1/queries/1')->assertUnauthorized();
        $this->putJson('/api/v1/queries/1', [])->assertUnauthorized();
        $this->deleteJson('/api/v1/queries/1')->assertUnauthorized();
    }

    public function test_an_authenticated_admin_can_create_a_query_record(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/queries', [
            'name' => 'Morgan Lee',
            'phone' => '03001112222',
            'service' => 'Vehicle transport',
            'pickup_location' => 'Lahore',
            'dropoff_location' => 'Multan',
            'message' => 'Please contact me.',
            'status' => 'new',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Morgan Lee')
            ->assertJsonPath('data.status', 'new');

        $this->assertDatabaseHas('queries', [
            'name' => 'Morgan Lee',
            'status' => 'new',
        ]);
    }

    public function test_queries_can_be_searched_filtered_and_viewed(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $query = Query::create([
            'name' => 'Jordan Smith',
            'phone' => '03001234567',
            'service' => 'Vehicle transport',
            'pickup_location' => 'Lahore',
            'dropoff_location' => 'Karachi',
            'message' => 'Please call after 3pm',
        ]);
        Query::create([
            'name' => 'Taylor Jones',
            'phone' => '03007654321',
            'status' => 'contacted',
        ]);

        $this->getJson('/api/v1/queries?search=Lahore')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $query->id);
        $this->getJson('/api/v1/queries?status=contacted')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'contacted');
        $this->getJson("/api/v1/queries/{$query->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'Please call after 3pm');
    }

    public function test_query_fields_and_status_can_be_updated_but_status_is_restricted(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $query = Query::create([
            'name' => 'Jordan Smith',
            'phone' => '03001234567',
        ]);

        $this->putJson("/api/v1/queries/{$query->id}", [
            'name' => 'Jordan A. Smith',
            'phone' => '03009999999',
            'service' => 'Fleet relocation',
            'pickup_location' => 'Islamabad',
            'dropoff_location' => 'Quetta',
            'message' => 'Updated request details',
            'status' => 'in_progress',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Jordan A. Smith')
            ->assertJsonPath('data.status', 'in_progress');

        $this->putJson("/api/v1/queries/{$query->id}", [
            'name' => 'Jordan A. Smith',
            'phone' => '03009999999',
            'status' => 'invalid',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_deleting_a_query_soft_deletes_it_and_hides_it_from_management(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $query = Query::create([
            'name' => 'Jordan Smith',
            'phone' => '03001234567',
        ]);

        $this->deleteJson("/api/v1/queries/{$query->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Query deleted.');

        $this->assertSoftDeleted($query);
        $this->getJson('/api/v1/queries')->assertOk()->assertJsonCount(0, 'data');
    }
}
