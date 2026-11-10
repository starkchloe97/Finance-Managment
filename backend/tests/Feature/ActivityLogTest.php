<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_changes_are_recorded_with_actor_and_safe_field_values(): void
    {
        $user = User::factory()->create(['name' => 'Ather']);
        Sanctum::actingAs($user);
        $this->actingAs($user);

        $customer = Customer::create([
            'code' => 'CUS-000001',
            'name' => 'Acme Logistics',
            'phone' => '+92 300 1234567',
        ]);
        $customer->update(['name' => 'Acme Transport']);
        $customer->delete();

        $this->assertSame(3, ActivityLog::count());
        $created = ActivityLog::where('action', 'created')->sole();
        $this->assertSame($user->id, $created->user_id);
        $this->assertSame('Customer', $created->subject_type);
        $this->assertSame('Created Customer: CUS-000001', $created->description);
        $this->assertSame('Acme Logistics', $created->changes['name']['new']);
        $this->assertArrayNotHasKey('phone', $created->changes);

        $updated = ActivityLog::where('action', 'updated')->sole();
        $this->assertSame('Acme Logistics', $updated->changes['name']['old']);
        $this->assertSame('Acme Transport', $updated->changes['name']['new']);
    }

    public function test_activity_feed_is_authenticated_and_paginated_newest_first(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->actingAs($user);

        Customer::create(['code' => 'CUS-000001', 'name' => 'First customer']);
        Customer::create(['code' => 'CUS-000002', 'name' => 'Second customer']);

        $this->getJson('/api/v1/activities?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.description', 'Created Customer: CUS-000002')
            ->assertJsonPath('data.0.actor', $user->name)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_activity_feed_requires_authentication(): void
    {
        $this->getJson('/api/v1/activities')->assertUnauthorized();
    }

    public function test_auth_tokens_are_not_recorded_or_returned_in_the_activity_feed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->actingAs($user);

        $user->createToken('transport-erp');
        $this->assertSame(0, ActivityLog::count());

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'updated',
            'subject_type' => 'PersonalAccessToken',
            'subject_id' => '1',
            'description' => 'Updated Personal Access Token: transport-erp',
        ]);
        Customer::create(['code' => 'CUS-000001', 'name' => 'Monitorable customer']);

        $this->assertSame(2, ActivityLog::count());
        $this->getJson('/api/v1/activities')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject_type', 'Customer')
            ->assertDontSee('Updated Personal Access Token: transport-erp');
    }

    public function test_bank_transactions_name_the_account_without_logging_account_numbers(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->actingAs($user);

        $account = BankAccount::create([
            'name' => 'Operating account',
            'bank_name' => 'Example Bank',
            'account_number' => '1234567890',
            'opening_balance' => 1000,
            'current_balance' => 1000,
        ]);
        BankTransaction::create([
            'bank_account_id' => $account->id,
            'type' => 'credit',
            'amount' => 250,
            'balance_after' => 1250,
            'description' => 'Invoice receipt',
            'transaction_date' => '2026-11-10',
        ]);

        $activity = ActivityLog::where('subject_type', 'BankTransaction')->sole();
        $this->assertSame('Created Bank Transaction — Operating account', $activity->description);
        $this->assertSame('Operating account', $activity->changes['bank_account']['new']);
        $this->assertArrayNotHasKey('account_number', $activity->changes);
    }
}
