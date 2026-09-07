<?php

namespace Database\Seeders;

use App\Enums\CompanyCapitalTransactionType;
use App\Enums\LoanBorrowerType;
use App\Enums\LoanStatus;
use App\Models\CompanyCapitalAccount;
use App\Models\CompanyCapitalTransaction;
use App\Models\Investor;
use App\Models\Loan;
use App\Models\LoanBorrower;
use App\Models\LoanRepayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LoanSeeder extends Seeder
{
    private int $loanSequence = 0;

    public function run(): void
    {
        $user = User::first();
        $investors = Investor::all();
        $borrowers = LoanBorrower::all();
        $account = CompanyCapitalAccount::where('code', 'MAIN')->first();

        $loanDate = Carbon::parse(fake()->dateTimeBetween('-8 months', '-1 month'));

        // Mix of investor and outsider loans
        $configs = [
            ['status' => LoanStatus::Active, 'type' => LoanBorrowerType::Investor, 'amount' => 400000],
            ['status' => LoanStatus::Active, 'type' => LoanBorrowerType::Outsider, 'amount' => 250000],
            ['status' => LoanStatus::Paid, 'type' => LoanBorrowerType::Investor, 'amount' => 150000],
            ['status' => LoanStatus::Paid, 'type' => LoanBorrowerType::Outsider, 'amount' => 300000],
            ['status' => LoanStatus::Cancelled, 'type' => LoanBorrowerType::Outsider, 'amount' => 100000],
            ['status' => LoanStatus::Active, 'type' => LoanBorrowerType::Investor, 'amount' => 500000],
        ];

        foreach ($configs as $config) {
            $this->loanSequence++;

            $borrowerType = $config['type'];
            $investorId = $borrowerType === LoanBorrowerType::Investor ? $investors->random()->id : null;
            $borrowerId = $borrowerType === LoanBorrowerType::Outsider ? $borrowers->random()->id : null;

            $amount = $config['amount'];
            $status = $config['status'];
            $currentLoanDate = $loanDate->copy()->addDays(fake()->numberBetween(1, 30));
            $dueDate = $currentLoanDate->copy()->addMonths(fake()->numberBetween(3, 12));

            $loan = Loan::create([
                'loan_code' => 'LOAN-'.str_pad((string) $this->loanSequence, 6, '0', STR_PAD_LEFT),
                'borrower_type' => $borrowerType,
                'investor_id' => $investorId,
                'loan_borrower_id' => $borrowerId,
                'amount' => $amount,
                'loan_date' => $currentLoanDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => $status,
                'paid_at' => $status === LoanStatus::Paid ? $dueDate->copy()->subDays(fake()->numberBetween(5, 20)) : null,
                'cancelled_at' => $status === LoanStatus::Cancelled ? $currentLoanDate->copy()->addDays(fake()->numberBetween(5, 30)) : null,
                'notes' => fake()->optional(0.3)->sentence(),
                'created_by' => $user?->id,
            ]);

            $this->recordLoanTransaction($account, CompanyCapitalTransactionType::LoanIssued, -$amount, $currentLoanDate, $loan, $user);

            if ($status === LoanStatus::Paid) {
                $repaymentAmount = $amount;
                LoanRepayment::create([
                    'loan_id' => $loan->id,
                    'amount' => $repaymentAmount,
                    'payment_date' => $loan->paid_at->toDateString(),
                    'reference' => 'REF-'.str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
                    'notes' => fake()->optional(0.3)->sentence(),
                    'created_by' => $user?->id,
                ]);

                $this->recordLoanTransaction($account, CompanyCapitalTransactionType::LoanRepayment, $repaymentAmount, $loan->paid_at, $loan, $user);
            }

            if ($status === LoanStatus::Cancelled) {
                $this->recordLoanTransaction($account, CompanyCapitalTransactionType::LoanCancelled, $amount, $loan->cancelled_at, $loan, $user);
            }
        }
    }

    private function recordLoanTransaction(
        ?CompanyCapitalAccount $account,
        CompanyCapitalTransactionType $type,
        float $amount,
        Carbon $date,
        Loan $loan,
        ?User $user,
    ): void {
        if (! $account) {
            return;
        }

        $lastId = (int) (CompanyCapitalTransaction::query()->max('id') ?? 0) + 1;

        CompanyCapitalTransaction::create([
            'company_capital_account_id' => $account->id,
            'transaction_code' => 'CAP-'.str_pad((string) $lastId, 6, '0', STR_PAD_LEFT),
            'type' => $type,
            'amount' => round($amount, 2),
            'available' => true,
            'transaction_date' => $date->toDateString(),
            'reference_type' => $loan->getMorphClass(),
            'reference_id' => $loan->id,
            'description' => "{$type->name} for loan {$loan->loan_code}",
            'created_by' => $user?->id,
            'created_at' => $date->copy()->startOfDay(),
        ]);
    }
}
