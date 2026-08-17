<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerSearchTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $attributes): Customer
    {
        return Customer::create(array_merge([
            'code' => 'CUS-'.str_pad((string) (Customer::count() + 1), 6, '0', STR_PAD_LEFT),
        ], $attributes));
    }

    public function test_search_matches_name_phone_and_company(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->customer(['name' => 'Acme Logistics', 'phone' => '0300-1111111', 'company' => 'Acme Pvt Ltd']);
        $this->customer(['name' => 'Zenith Freight', 'phone' => '0300-2222222', 'company' => 'Zenith Ltd']);

        $this->getJson('/api/v1/customers?search=Acme')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/customers?search=2222222')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/customers?search=Zenith+Ltd')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/customers?search=nobody')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_a_deleted_customer_stays_deleted_whichever_field_matches(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $gone = $this->customer([
            'name' => 'Former Client',
            'phone' => '0300-9999999',
            'company' => 'Former Holdings',
        ]);

        $gone->delete();

        // The soft-delete scope is a separate `where`. With the search terms
        // ungrouped it only guarded the first of them, so a match on phone or
        // company used to bring the row back.
        foreach (['Former Client', '0300-9999999', 'Former Holdings'] as $term) {
            $this->getJson('/api/v1/customers?search='.urlencode($term))
                ->assertOk()
                ->assertJsonCount(0, 'data');
        }
    }

    public function test_the_dropdown_can_ask_for_more_than_one_page(): void
    {
        Sanctum::actingAs(User::factory()->create());

        for ($i = 0; $i < 12; $i++) {
            $this->customer(['name' => "Customer {$i}"]);
        }

        // The default page is 10, which used to be every customer the estimate
        // form could offer.
        $this->getJson('/api/v1/customers')->assertOk()->assertJsonCount(10, 'data');
        $this->getJson('/api/v1/customers?per_page=100')->assertOk()->assertJsonCount(12, 'data');

        // Capped, so the page size cannot be used to pull the whole table.
        $this->getJson('/api/v1/customers?per_page=9999')->assertOk()->assertJsonPath('meta.per_page', 100);
    }
}
