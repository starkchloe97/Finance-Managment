<?php

namespace Database\Seeders;

use App\Enums\CompanyCapitalDraftStatus;
use App\Enums\CompanyCapitalTransactionType;
use App\Models\CompanyCapitalAccount;
use App\Models\CompanyCapitalDraft;
use App\Models\CompanyCapitalDraftActivity;
use App\Models\CompanyCapitalTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CompanyCapitalSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $account = CompanyCapitalAccount::create([
            'code' => 'MAIN',
            'name' => 'Company capital',
            'is_active' => true,
        ]);

        $transactionDate = Carbon::parse(fake()->dateTimeBetween('-12 months', '-6 months'));

        $this->recordTransaction(
            $account,
            CompanyCapitalTransactionType::OpeningBalance,
            2500000,
            $transactionDate->toDateString(),
            'Opening company capital',
            $user,
            true,
        );

        // Additional capital injection
        $this->recordTransaction(
            $account,
            CompanyCapitalTransactionType::CapitalAdded,
            1000000,
            $transactionDate->copy()->addMonths(2)->toDateString(),
            'Additional capital from reserves',
            $user,
            true,
        );

        // Reserved capital
        $this->recordTransaction(
            $account,
            CompanyCapitalTransactionType::CapitalAdded,
            500000,
            $transactionDate->copy()->addMonths(3)->toDateString(),
            'Reserved capital contribution',
            $user,
            false,
        );

        // Drafts
        foreach (range(1, 2) as $i) {
            $draft = CompanyCapitalDraft::create([
                'company_capital_account_id' => $account->id,
                'amount' => fake()->randomFloat(2, 100000, 300000),
                'transaction_date' => Carbon::parse(fake()->dateTimeBetween('-2 months', 'today'))->toDateString(),
                'note' => fake()->sentence(),
                'status' => CompanyCapitalDraftStatus::Draft,
                'created_by' => $user?->id,
            ]);

            CompanyCapitalDraftActivity::create([
                'company_capital_draft_id' => $draft->id,
                'activity_type' => 'added',
                'note' => $draft->note,
                'created_by' => $user?->id,
            ]);
        }
    }

    private function recordTransaction(
        CompanyCapitalAccount $account,
        CompanyCapitalTransactionType $type,
        float $amount,
        string $transactionDate,
        string $description,
        ?User $user,
        bool $available,
    ): void {
        $lastId = (int) (CompanyCapitalTransaction::query()->max('id') ?? 0) + 1;

        CompanyCapitalTransaction::create([
            'company_capital_account_id' => $account->id,
            'transaction_code' => 'CAP-'.str_pad((string) $lastId, 6, '0', STR_PAD_LEFT),
            'type' => $type,
            'amount' => round($amount, 2),
            'available' => $available,
            'transaction_date' => $transactionDate,
            'reference_type' => null,
            'reference_id' => null,
            'description' => $description,
            'created_by' => $user?->id,
            'created_at' => Carbon::parse($transactionDate)->startOfDay(),
        ]);
    }
}
