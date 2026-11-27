<?php

namespace Tests\Feature;

use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvestorOwnerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_investor_creates_and_reuses_the_authenticated_users_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Company Owner',
            'email' => 'owner@example.test',
        ]);
        Sanctum::actingAs($user);

        $firstResponse = $this->postJson('/api/v1/my-investor')
            ->assertOk()
            ->assertJsonPath('data.name', 'Company Owner')
            ->assertJsonPath('data.email', 'owner@example.test');

        $investorId = $firstResponse->json('data.id');

        $this->postJson('/api/v1/my-investor')
            ->assertOk()
            ->assertJsonPath('data.id', $investorId);

        $this->assertDatabaseCount('investors', 1);
        $this->assertDatabaseHas('investors', [
            'id' => $investorId,
            'user_id' => $user->id,
        ]);
    }

    public function test_my_investor_links_an_existing_investor_with_the_users_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Company Owner',
            'email' => 'owner@example.test',
        ]);
        $investor = Investor::create([
            'investor_code' => 'INV-000001',
            'name' => 'Owner profile',
            'email' => 'OWNER@example.test',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/my-investor')
            ->assertOk()
            ->assertJsonPath('data.id', $investor->id);

        $this->assertDatabaseCount('investors', 1);
        $this->assertDatabaseHas('investors', [
            'id' => $investor->id,
            'user_id' => $user->id,
        ]);
    }
}
