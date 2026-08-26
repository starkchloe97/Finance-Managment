<?php

namespace App\Services;

use App\Enums\CompanyCapitalTransactionType;
use App\Enums\LoanBorrowerType;
use App\Enums\LoanStatus;
use App\Models\Investor;
use App\Models\Loan;
use App\Models\LoanBorrower;
use App\Models\LoanRepayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanService
{
    public function __construct(
        private CompanyCapitalService $capital,
        private LoanBorrowerService $borrowers,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $this->refreshOverdueStatuses();

        return Loan::query()
            ->with(['investor', 'borrower'])
            ->withSum('repayments', 'amount')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['investor_id'] ?? null, fn ($query, $investorId) => $query->where('investor_id', $investorId))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('loan_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('loan_date', '<=', $to))
            ->when($filters['borrower'] ?? null, function ($query, $borrower) {
                $query->where(function ($query) use ($borrower) {
                    $query->whereHas('investor', fn ($query) => $query->where('name', 'like', "%{$borrower}%"))
                        ->orWhereHas('borrower', fn ($query) => $query->where('name', 'like', "%{$borrower}%"));
                });
            })
            ->latest('loan_date')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function find(Loan $loan): Loan
    {
        $this->refreshOverdueStatus($loan);

        return Loan::query()
            ->with(['investor', 'borrower', 'repayments.creator'])
            ->withSum('repayments', 'amount')
            ->findOrFail($loan->id);
    }

    public function create(array $data, ?User $user): Loan
    {
        return DB::transaction(function () use ($data, $user) {
            $amount = round((float) $data['amount'], 2);
            $this->capital->assertAvailable($amount);

            [$investorId, $borrowerId] = $this->resolveBorrower($data);
            $loan = Loan::create([
                'loan_code' => $this->nextLoanCode(),
                'borrower_type' => $data['borrower_type'],
                'investor_id' => $investorId,
                'loan_borrower_id' => $borrowerId,
                'amount' => $amount,
                'loan_date' => $data['loan_date'],
                'due_date' => $data['due_date'],
                'status' => LoanStatus::Active,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user?->id,
            ]);

            $this->capital->record(
                CompanyCapitalTransactionType::LoanIssued,
                -$amount,
                $data['loan_date'],
                $loan,
                "Loan {$loan->loan_code} issued to {$loan->borrowerName()}",
                $user,
            );

            return $this->find($loan);
        });
    }

    public function update(Loan $loan, array $data): Loan
    {
        return DB::transaction(function () use ($loan, $data) {
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);
            $this->refreshOverdueStatus($loan);

            if (! in_array($loan->status, [LoanStatus::Active, LoanStatus::Overdue], true)) {
                $this->reject('loan', 'Only active or overdue loans can be updated.');
            }

            if (Carbon::parse($data['due_date'])->isBefore($loan->loan_date)) {
                $this->reject('due_date', 'Due date must be on or after the loan date.');
            }

            $loan->update(['due_date' => $data['due_date'], 'notes' => $data['notes'] ?? null]);
            $this->refreshOverdueStatus($loan->refresh());

            return $this->find($loan);
        });
    }

    public function repay(Loan $loan, array $data, ?User $user): LoanRepayment
    {
        return DB::transaction(function () use ($loan, $data, $user) {
            $loan = Loan::query()->withSum('repayments', 'amount')->lockForUpdate()->findOrFail($loan->id);
            $this->refreshOverdueStatus($loan);

            if (! in_array($loan->status, [LoanStatus::Active, LoanStatus::Overdue], true)) {
                $this->reject('loan', 'Only active or overdue loans can receive repayments.');
            }

            $amount = round((float) $data['amount'], 2);
            if ($amount > $loan->outstanding_amount) {
                $this->reject('amount', 'Repayment cannot exceed the outstanding amount.');
            }

            $repayment = LoanRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user?->id,
            ]);

            $this->capital->record(
                CompanyCapitalTransactionType::LoanRepayment,
                $amount,
                $data['payment_date'],
                $repayment,
                "Repayment recorded for {$loan->loan_code}",
                $user,
            );

            $loan->loadSum('repayments', 'amount');
            if ($loan->outstanding_amount === 0.0) {
                $loan->update(['status' => LoanStatus::Paid, 'paid_at' => now()]);
            } else {
                $this->refreshOverdueStatus($loan);
            }

            return $repayment->load('creator');
        });
    }

    public function cancel(Loan $loan, ?User $user): Loan
    {
        return DB::transaction(function () use ($loan, $user) {
            $loan = Loan::query()->withSum('repayments', 'amount')->lockForUpdate()->findOrFail($loan->id);
            $this->refreshOverdueStatus($loan);

            if (! in_array($loan->status, [LoanStatus::Active, LoanStatus::Overdue], true)) {
                $this->reject('loan', 'Only active or overdue loans can be cancelled.');
            }
            if ($loan->total_repaid > 0) {
                $this->reject('loan', 'Loans with repayments cannot be cancelled.');
            }

            $loan->update(['status' => LoanStatus::Cancelled, 'cancelled_at' => now()]);
            $this->capital->record(
                CompanyCapitalTransactionType::LoanCancelled,
                (float) $loan->amount,
                today()->toDateString(),
                $loan,
                "Cancelled loan {$loan->loan_code}",
                $user,
            );

            return $this->find($loan);
        });
    }

    public function investorLoans(Investor $investor): LengthAwarePaginator
    {
        $this->refreshOverdueStatuses();

        return Loan::query()
            ->where('investor_id', $investor->id)
            ->with(['investor', 'borrower'])
            ->withSum('repayments', 'amount')
            ->latest('loan_date')
            ->paginate(15);
    }

    private function resolveBorrower(array $data): array
    {
        if ($data['borrower_type'] === LoanBorrowerType::Investor->value) {
            Investor::query()->findOrFail($data['investor_id']);

            return [$data['investor_id'], null];
        }

        if (! empty($data['loan_borrower_id'])) {
            LoanBorrower::query()->findOrFail($data['loan_borrower_id']);

            return [null, $data['loan_borrower_id']];
        }

        $borrower = $this->borrowers->create([
            'name' => $data['outsider_name'],
            'email' => $data['outsider_email'] ?? null,
            'phone' => $data['outsider_phone'] ?? null,
            'address' => $data['outsider_address'] ?? null,
        ]);

        return [null, $borrower->id];
    }

    private function refreshOverdueStatuses(): void
    {
        Loan::query()
            ->whereIn('status', [LoanStatus::Active->value, LoanStatus::Overdue->value])
            ->orderBy('id')
            ->each(fn (Loan $loan) => $this->refreshOverdueStatus($loan));
    }

    private function refreshOverdueStatus(Loan $loan): void
    {
        if (! in_array($loan->status, [LoanStatus::Active, LoanStatus::Overdue], true)) {
            return;
        }

        $outstanding = $loan->outstanding_amount;
        $isOverdue = $loan->due_date->isBefore(today()) && $outstanding > 0;
        $newStatus = $isOverdue ? LoanStatus::Overdue : LoanStatus::Active;

        if ($loan->status !== $newStatus || ($isOverdue && ! $loan->first_overdue_at)) {
            $loan->update([
                'status' => $newStatus,
                'first_overdue_at' => $isOverdue && ! $loan->first_overdue_at ? now() : $loan->first_overdue_at,
            ]);
        }
    }

    private function nextLoanCode(): string
    {
        $nextNumber = (int) (Loan::query()->max('id') ?? 0) + 1;

        return 'LOAN-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
