<?php

namespace Tests\Feature;

use App\Models\AccountingPeriodClose;
use App\Models\User;
use App\Services\AccountingPeriodService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountingPeriodCloseTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_period_close_saves_snapshot_and_blocks_writes_in_that_period(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/accounting-periods', [
            'period_type' => 'month',
            'period_label' => '2020-03',
        ])
            ->assertCreated()
            ->assertJsonPath('data.period_start', '2020-03-01T00:00:00.000000Z')
            ->assertJsonPath('data.period_end', '2020-03-31T00:00:00.000000Z')
            ->assertJsonPath('data.summary.report.period.from', '2020-03-01');

        $this->assertDatabaseCount('accounting_period_closes', 1);
        $this->assertDatabaseHas('accounting_period_closes', [
            'period_type' => 'month',
            'period_label' => '2020-03',
        ]);

        try {
            app(AccountingPeriodService::class)->assertOpen('2020-03-15');
            $this->fail('A transaction in a closed period should be rejected.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        try {
            app(InvoiceService::class)->create(['invoice_date' => '2020-03-20'], 1);
            $this->fail('Invoice creation in a closed period should be rejected.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        app(AccountingPeriodService::class)->assertOpen('2020-04-01');
    }

    public function test_current_period_cannot_be_closed_and_duplicate_close_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/accounting-periods', [
            'period_type' => 'month',
            'period_label' => today()->format('Y-m'),
        ])->assertUnprocessable();

        AccountingPeriodClose::query()->create([
            'period_type' => 'year',
            'period_label' => '2020',
            'period_start' => '2020-01-01',
            'period_end' => '2020-12-31',
            'closed_at' => now(),
            'summary' => [],
        ]);

        $this->postJson('/api/v1/accounting-periods', [
            'period_type' => 'year',
            'period_label' => '2020',
        ])->assertUnprocessable();
    }
}
