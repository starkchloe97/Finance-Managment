<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\TransportJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_invoice_number_and_line_total_are_calculated_by_backend(): void
    {
        $invoice = $this->createInvoice([
            'items' => [
                ['description' => 'Rental', 'quantity' => 3, 'unit' => 'vehicle', 'unit_price' => 110000],
            ],
            'tax_amount' => 10,
        ]);

        $this->assertSame('SI-000001', $invoice['invoice_no']);
        $this->assertSame('330000.00', $invoice['subtotal']);
        $this->assertSame('330010.00', $invoice['total']);
        $this->assertSame('unpaid', $invoice['status']);
        $this->assertSame(330010.0, (float) $invoice['outstanding_amount']);
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice['id'], 'amount' => 330000]);
    }

    public function test_partial_and_full_payments_update_invoice_balance_and_status(): void
    {
        $invoice = $this->createInvoice(['items' => [
            ['description' => 'Transport', 'quantity' => 1, 'unit_price' => 330000],
        ]]);

        $this->recordPayment('received', $invoice['id'], 100000)->assertCreated();
        $this->getJson("/api/v1/invoices/{$invoice['id']}")
            ->assertOk()
            ->assertJsonPath('data.paid_amount', 100000)
            ->assertJsonPath('data.outstanding_amount', 230000)
            ->assertJsonPath('data.status', 'partially_paid')
            ->assertJsonCount(1, 'data.payments');

        $this->recordPayment('received', $invoice['id'], 230000)->assertCreated();
        $this->getJson("/api/v1/invoices/{$invoice['id']}")
            ->assertOk()
            ->assertJsonPath('data.paid_amount', 330000)
            ->assertJsonPath('data.outstanding_amount', 0)
            ->assertJsonPath('data.status', 'paid');
    }

    public function test_payables_use_paid_direction_and_are_independent_from_receivables(): void
    {
        $invoice = $this->createInvoice([
            'direction' => 'payable',
            'category' => 'administrative',
            'party_name' => 'Office supplier',
            'items' => [['description' => 'Office expense', 'quantity' => 1, 'unit_price' => 90000]],
        ]);

        $this->recordPayment('received', $invoice['id'], 50000)->assertUnprocessable();
        $this->recordPayment('paid', $invoice['id'], 50000)->assertCreated();
        $this->getJson("/api/v1/invoices/{$invoice['id']}")
            ->assertOk()->assertJsonPath('data.status', 'partially_paid')
            ->assertJsonPath('data.outstanding_amount', 40000);
    }

    public function test_zero_value_invoice_cannot_be_created_as_already_paid(): void
    {
        $this->postJson('/api/v1/invoices', [
            'direction' => 'receivable',
            'category' => 'sales',
            'party_name' => 'Test customer',
            'invoice_date' => today()->toDateString(),
            'items' => [['description' => 'Zero amount', 'quantity' => 1, 'unit_price' => 0]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items']);

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_payment_can_be_allocated_across_invoices_but_cannot_exceed_balances(): void
    {
        $a = $this->createInvoice(['items' => [['description' => 'One', 'quantity' => 1, 'unit_price' => 200]]]);
        $b = $this->createInvoice(['items' => [['description' => 'Two', 'quantity' => 1, 'unit_price' => 300]]]);
        $this->postJson('/api/v1/payments', [
            'direction' => 'received',
            'payment_date' => today()->toDateString(),
            'amount' => 500,
            'allocations' => [
                ['invoice_id' => $a['id'], 'amount' => 200],
                ['invoice_id' => $b['id'], 'amount' => 300],
            ],
        ])->assertCreated();

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('invoice_payments', 2);
        $this->assertSame('paid', Invoice::findOrFail($a['id'])->status);
        $this->assertSame('paid', Invoice::findOrFail($b['id'])->status);

        $this->postJson('/api/v1/payments', [
            'direction' => 'received',
            'payment_date' => today()->toDateString(),
            'amount' => 1,
            'allocations' => [['invoice_id' => $a['id'], 'amount' => 1]],
        ])->assertUnprocessable();
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_invoice_is_overdue_when_balance_remains_after_due_date(): void
    {
        $invoice = $this->createInvoice([
            'invoice_date' => today()->subDays(3)->toDateString(),
            'due_date' => today()->subDay()->toDateString(),
            'items' => [['description' => 'Commission', 'quantity' => 1, 'unit_price' => 50000]],
            'category' => 'commission',
        ]);

        $this->assertSame('overdue', $invoice['status']);
        $this->recordPayment('received', $invoice['id'], 20000)->assertCreated();
        $this->getJson("/api/v1/invoices/{$invoice['id']}")
            ->assertOk()->assertJsonPath('data.status', 'overdue')
            ->assertJsonPath('data.outstanding_amount', 30000);
    }

    public function test_completed_job_generates_only_one_receivable_without_changing_profit(): void
    {
        $customer = Customer::create(['code' => 'CUS-JOB-INV', 'name' => 'Job customer']);
        $estimate = Estimate::create([
            'code' => 'EST-JOB-INV',
            'customer_id' => $customer->id,
            'estimate_date' => today(),
            'pickup' => 'KHI',
            'destination' => 'LHR',
            'service_type' => 'goods',
            'status' => 'accepted',
            'estimated_sell' => 170000,
        ]);
        $job = TransportJob::create([
            'code' => 'JOB-INVOICE',
            'estimate_id' => $estimate->id,
            'customer_id' => $customer->id,
            'job_date' => today(),
            'status' => JobStatus::Completed,
            'sell_price' => 170000,
            'cost_price' => 120000,
            'base_profit' => 50000,
            'extra_costs' => 0,
            'final_profit' => 50000,
        ]);

        $response = $this->postJson("/api/v1/jobs/{$job->id}/invoice")->assertCreated();
        $this->assertSame(170000.0, (float) $response->json('data.total'));
        $this->assertSame('receivable', $response->json('data.direction'));
        $this->postJson("/api/v1/jobs/{$job->id}/invoice")->assertUnprocessable();
        $this->assertSame(50000.0, (float) $job->fresh()->final_profit);
    }

    public function test_commission_receivable_does_not_add_company_capital(): void
    {
        $invoice = $this->createInvoice([
            'category' => 'commission',
            'items' => [['description' => 'Commission earned', 'quantity' => 1, 'unit_price' => 50000]],
        ]);
        $this->recordPayment('received', $invoice['id'], 20000)->assertCreated();
        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('kpis.accounting.commission_receivable', 30000);
        $this->assertDatabaseCount('company_capital_transactions', 0);
    }

    public function test_financial_endpoints_require_authentication(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->getJson('/api/v1/invoices')->assertUnauthorized();
        $this->postJson('/api/v1/payments', [])->assertUnauthorized();
    }

    private function createInvoice(array $overrides = []): array
    {
        $defaults = [
            'direction' => 'receivable',
            'category' => 'sales',
            'party_name' => 'Test customer',
            'invoice_date' => today()->toDateString(),
            'items' => [['description' => 'Service', 'quantity' => 1, 'unit_price' => 100]],
        ];

        return $this->postJson('/api/v1/invoices', array_replace_recursive($defaults, $overrides))
            ->assertCreated()
            ->json('data');
    }

    private function recordPayment(string $direction, int $invoiceId, float $amount)
    {
        return $this->postJson('/api/v1/payments', [
            'direction' => $direction,
            'payment_date' => today()->toDateString(),
            'amount' => $amount,
            'allocations' => [['invoice_id' => $invoiceId, 'amount' => $amount]],
        ]);
    }
}
