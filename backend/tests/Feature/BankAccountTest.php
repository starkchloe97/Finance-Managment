<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BankAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_bank_account_starts_with_opening_balance_and_lists_it_in_history(): void
    {
        $account = $this->postJson('/api/v1/bank-accounts', [
            'name' => 'Main operating account',
            'bank_name' => 'Example Bank',
            'account_number' => '123456789',
            'branch_name' => 'Central',
            'opening_balance' => 1250.75,
        ])->assertCreated()
            ->assertJsonPath('data.current_balance', '1250.75')
            ->json('data');

        $this->getJson('/api/v1/bank-accounts')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Main operating account');
        $this->getJson("/api/v1/bank-accounts/{$account['id']}/transactions")
            ->assertOk()
            ->assertJsonPath('data.0.type', 'opening_balance')
            ->assertJsonPath('data.0.amount', '1250.75')
            ->assertJsonPath('data.0.balance_after', '1250.75');
    }

    public function test_invoice_receipts_and_payments_update_the_selected_bank_balance(): void
    {
        $account = $this->createAccount(500);
        $receivable = $this->createInvoice('receivable', 250);
        $payable = $this->createInvoice('payable', 200);

        $this->recordPayment($account['id'], $receivable['id'], 'received', 250)
            ->assertCreated()
            ->assertJsonPath('data.bank_account_id', $account['id'])
            ->assertJsonPath('data.bank_account_name', 'Cash management');
        $this->recordPayment($account['id'], $payable['id'], 'paid', 200)->assertCreated();

        $this->getJson("/api/v1/bank-accounts/{$account['id']}")
            ->assertOk()
            ->assertJsonPath('data.current_balance', '550.00');
        $this->getJson("/api/v1/bank-accounts/{$account['id']}/transactions")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.type', 'payment')
            ->assertJsonPath('data.0.balance_after', '550.00')
            ->assertJsonPath('data.1.type', 'receipt')
            ->assertJsonPath('data.1.balance_after', '750.00');
    }

    public function test_payable_cannot_overdraw_bank_account_and_payment_requires_bank_account(): void
    {
        $account = $this->createAccount(100);
        $payable = $this->createInvoice('payable', 150);
        $payload = [
            'direction' => 'paid',
            'payment_date' => today()->toDateString(),
            'amount' => 150,
            'allocations' => [['invoice_id' => $payable['id'], 'amount' => 150]],
        ];

        $this->postJson('/api/v1/payments', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bank_account_id']);
        $this->postJson('/api/v1/payments', ['bank_account_id' => $account['id']] + $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bank_account_id']);

        $this->assertDatabaseCount('payments', 0);
        $this->getJson("/api/v1/bank-accounts/{$account['id']}")
            ->assertOk()
            ->assertJsonPath('data.current_balance', '100.00');
    }

    private function createAccount(float $openingBalance): array
    {
        return $this->postJson('/api/v1/bank-accounts', [
            'name' => 'Cash management',
            'opening_balance' => $openingBalance,
        ])->assertCreated()->json('data');
    }

    private function createInvoice(string $direction, float $amount): array
    {
        return $this->postJson('/api/v1/invoices', [
            'direction' => $direction,
            'category' => $direction === 'receivable' ? 'sales' : 'administrative',
            'party_name' => 'Invoice party',
            'invoice_date' => today()->toDateString(),
            'items' => [[
                'description' => 'Invoice line',
                'quantity' => 1,
                'unit_price' => $amount,
            ]],
        ])->assertCreated()->json('data');
    }

    private function recordPayment(int $bankAccountId, int $invoiceId, string $direction, float $amount)
    {
        return $this->postJson('/api/v1/payments', [
            'bank_account_id' => $bankAccountId,
            'direction' => $direction,
            'payment_date' => today()->toDateString(),
            'amount' => $amount,
            'allocations' => [['invoice_id' => $invoiceId, 'amount' => $amount]],
        ]);
    }
}
