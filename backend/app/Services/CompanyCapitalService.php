<?php

namespace App\Services;

use App\Enums\CompanyCapitalDraftStatus;
use App\Enums\CompanyCapitalTransactionType;
use App\Models\CompanyCapitalAccount;
use App\Models\CompanyCapitalDraft;
use App\Models\CompanyCapitalDraftActivity;
use App\Models\CompanyCapitalTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyCapitalService
{
    public function snapshot(): array
    {
        $account = CompanyCapitalAccount::query()->where('code', 'MAIN')->first();

        if (! $account) {
            return $this->emptySnapshot($this->pendingDrafts(), $this->draftHistory());
        }

        $transactions = $account->transactions()->latest('transaction_date')->latest('id')->limit(50)->get();

        return [
            'initialized' => $account->transactions()->exists(),
            'account' => $account,
            'opening_balance' => $this->openingBalance($account),
            'available_to_lend' => $this->availableBalance($account),
            'lent_out' => $this->lentOut($account),
            'reserved' => $this->reservedCapital($account),
            'total_capital' => $this->totalCapital($account),
            'current_balance' => $this->availableBalance($account),
            'transactions' => $transactions,
            'drafts' => $this->pendingDrafts(),
            'draft_history' => $this->draftHistory(),
        ];
    }

    public function initialize(float $amount, string $transactionDate, ?User $user): array
    {
        return DB::transaction(function () use ($amount, $transactionDate, $user) {
            $account = CompanyCapitalAccount::query()->where('code', 'MAIN')->lockForUpdate()->first();
            if (! $account) {
                $account = CompanyCapitalAccount::create(['code' => 'MAIN', 'name' => 'Company capital', 'is_active' => true]);
                $account = CompanyCapitalAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();
            }

            if ($account->transactions()->exists()) {
                $this->reject('amount', 'Opening capital is already configured and cannot be changed after transactions exist.');
            }

            $this->recordLocked(
                $account,
                CompanyCapitalTransactionType::OpeningBalance,
                $amount,
                $transactionDate,
                null,
                'Opening company capital',
                $user,
                true,
            );

            return $this->snapshot();
        });
    }

    public function addCapital(float $amount, string $transactionDate, bool $available, ?string $notes, ?User $user): array
    {
        return DB::transaction(function () use ($amount, $transactionDate, $available, $notes, $user) {
            $account = $this->activeAccountForUpdate();

            $this->recordLocked(
                $account,
                CompanyCapitalTransactionType::CapitalAdded,
                $amount,
                $transactionDate,
                null,
                $notes ?? 'Additional company capital',
                $user,
                $available,
            );

            return $this->snapshot();
        });
    }

    public function withdrawCapital(float $amount, string $transactionDate, ?string $notes, ?User $user): array
    {
        return DB::transaction(function () use ($amount, $transactionDate, $notes, $user) {
            $account = $this->activeAccountForUpdate();

            $available = $this->availableBalance($account);
            if ($available < $amount) {
                $this->reject(
                    'amount',
                    "Only {$this->formatMoney($available)} is currently available to withdraw.",
                );
            }

            $this->recordLocked(
                $account,
                CompanyCapitalTransactionType::CapitalWithdrawn,
                -$amount,
                $transactionDate,
                null,
                $notes ?? 'Capital withdrawn',
                $user,
                true,
            );

            return $this->snapshot();
        });
    }

    public function makeAvailable(int $transactionId): array
    {
        return DB::transaction(function () use ($transactionId) {
            $account = $this->activeAccountForUpdate();

            $transaction = $account->transactions()
                ->where('type', CompanyCapitalTransactionType::CapitalAdded->value)
                ->where('available', false)
                ->lockForUpdate()
                ->findOrFail($transactionId);

            $transaction->update(['available' => true]);

            $this->recordLocked(
                $account,
                CompanyCapitalTransactionType::CapitalMadeAvailable,
                0,
                today()->toDateString(),
                $transaction,
                'Capital made available for company operations',
                null,
                true,
            );

            return $this->snapshot();
        });
    }

    public function makeUnavailable(int $transactionId): array
    {
        return DB::transaction(function () use ($transactionId) {
            $account = $this->activeAccountForUpdate();

            $transaction = $account->transactions()
                ->where('type', CompanyCapitalTransactionType::CapitalAdded->value)
                ->where('available', true)
                ->lockForUpdate()
                ->findOrFail($transactionId);

            $available = $this->availableBalance($account);
            if ($available < (float) $transaction->amount) {
                $this->reject(
                    'available',
                    'Cannot make this capital unavailable because available capital is insufficient.',
                );
            }

            $transaction->update(['available' => false]);

            $this->recordLocked(
                $account,
                CompanyCapitalTransactionType::CapitalReserved,
                0,
                today()->toDateString(),
                $transaction,
                'Capital reserved and marked as unavailable',
                null,
                true,
            );

            return $this->snapshot();
        });
    }

    public function assertAvailable(float $amount): void
    {
        $account = $this->activeAccountForUpdate();
        $available = $this->availableBalance($account);
        if ($available < $amount) {
            $this->reject(
                'amount',
                "Only {$this->formatMoney($available)} is currently available to lend.",
            );
        }
    }

    public function record(
        CompanyCapitalTransactionType $type,
        float $amount,
        string $transactionDate,
        ?Model $reference,
        string $description,
        ?User $user,
        ?bool $available = true,
    ): CompanyCapitalTransaction {
        $account = $this->activeAccountForUpdate();

        return $this->recordLocked($account, $type, $amount, $transactionDate, $reference, $description, $user, $available);
    }

    public function createDraft(float $amount, string $transactionDate, string $note, ?User $user): CompanyCapitalDraft
    {
        return DB::transaction(function () use ($amount, $transactionDate, $note, $user) {
            $account = CompanyCapitalAccount::query()->where('code', 'MAIN')->first();

            $draft = CompanyCapitalDraft::create([
                'company_capital_account_id' => $account?->id,
                'amount' => round($amount, 2),
                'transaction_date' => $transactionDate,
                'note' => $note,
                'status' => CompanyCapitalDraftStatus::Draft,
                'created_by' => $user?->id,
            ]);

            CompanyCapitalDraftActivity::create([
                'company_capital_draft_id' => $draft->id,
                'activity_type' => 'added',
                'note' => $note,
                'created_by' => $user?->id,
            ]);

            return $draft;
        });
    }

    public function convertDraft(int $draftId, bool $available, ?User $user): array
    {
        return DB::transaction(function () use ($draftId, $available, $user) {
            $draft = CompanyCapitalDraft::where('status', CompanyCapitalDraftStatus::Draft->value)
                ->lockForUpdate()
                ->findOrFail($draftId);

            $account = $this->activeAccountForUpdate();

            $transaction = $this->recordLocked(
                $account,
                CompanyCapitalTransactionType::CapitalAdded,
                (float) $draft->amount,
                $draft->transaction_date->toDateString(),
                null,
                $draft->note ?? 'Converted from capital draft',
                $user,
                $available,
            );

            $draft->update([
                'status' => CompanyCapitalDraftStatus::Converted,
                'company_capital_transaction_id' => $transaction->id,
            ]);

            CompanyCapitalDraftActivity::create([
                'company_capital_draft_id' => $draft->id,
                'activity_type' => 'converted',
                'note' => $available
                    ? 'Added as available company capital'
                    : 'Added as reserved company capital',
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'available' => $available,
                ],
                'created_by' => $user?->id,
            ]);

            return $this->snapshot();
        });
    }

    public function removeDraft(int $draftId, string $removalNote, ?User $user): array
    {
        return DB::transaction(function () use ($draftId, $removalNote, $user) {
            $draft = CompanyCapitalDraft::where('status', CompanyCapitalDraftStatus::Draft->value)
                ->lockForUpdate()
                ->findOrFail($draftId);

            $draft->update([
                'status' => CompanyCapitalDraftStatus::Removed,
                'removed_at' => now(),
                'removal_note' => $removalNote,
                'removed_by' => $user?->id,
            ]);

            CompanyCapitalDraftActivity::create([
                'company_capital_draft_id' => $draft->id,
                'activity_type' => 'removed',
                'note' => $removalNote,
                'metadata' => [
                    'removed_by' => $user?->id,
                ],
                'created_by' => $user?->id,
            ]);

            return $this->snapshot();
        });
    }

    private function pendingDrafts(): array
    {
        return CompanyCapitalDraft::where('status', CompanyCapitalDraftStatus::Draft->value)
            ->with('creator:id,name', 'transaction:id,transaction_code,type')
            ->latest('id')
            ->get()
            ->map(fn ($draft) => [
                'id' => $draft->id,
                'amount' => $draft->amount,
                'transaction_date' => $draft->transaction_date?->toDateString(),
                'note' => $draft->note,
                'transaction_code' => $draft->transaction_code ?? 'DRFT-' . str_pad((string) $draft->id, 6, '0', STR_PAD_LEFT),
                'creator_name' => $draft->creator?->name,
                'created_at' => $draft->created_at,
            ])
            ->values()
            ->toArray();
    }

    private function draftHistory(): array
    {
        return CompanyCapitalDraftActivity::with('draft')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($activity) => [
                'id' => 'draft-act-'.$activity->id,
                'type' => 'capital_draft',
                'amount' => (string) $activity->draft->amount,
                'transaction_date' => $activity->created_at->toDateString(),
                'description' => $activity->note,
                'draft_status' => $activity->activity_type,
                'transaction_code' => 'DRFT-'.str_pad((string) $activity->draft->id, 6, '0', STR_PAD_LEFT),
                'created_at' => $activity->created_at,
            ])
            ->values()
            ->toArray();
    }

    private function emptySnapshot($drafts = [], $draftHistory = []): array
    {
        return [
            'initialized' => false,
            'account' => null,
            'opening_balance' => 0.0,
            'available_to_lend' => 0.0,
            'lent_out' => 0.0,
            'reserved' => 0.0,
            'total_capital' => 0.0,
            'current_balance' => 0.0,
            'transactions' => [],
            'drafts' => $drafts,
            'draft_history' => $draftHistory,
        ];
    }

    private function openingBalance(CompanyCapitalAccount $account): float
    {
        return (float) $account->transactions()
            ->where('type', CompanyCapitalTransactionType::OpeningBalance->value)
            ->sum('amount');
    }

    private function availableBalance(CompanyCapitalAccount $account): float
    {
        $totalSum = (float) $account->transactions()->sum('amount');
        $unavailableAdded = (float) $account->transactions()
            ->where('type', CompanyCapitalTransactionType::CapitalAdded->value)
            ->where('available', false)
            ->sum('amount');

        return round($totalSum - $unavailableAdded, 2);
    }

    private function totalCapital(CompanyCapitalAccount $account): float
    {
        return (float) $account->transactions()
            ->whereIn('type', [
                CompanyCapitalTransactionType::OpeningBalance->value,
                CompanyCapitalTransactionType::CapitalAdded->value,
                CompanyCapitalTransactionType::CapitalWithdrawn->value,
            ])
            ->sum('amount');
    }

    private function reservedCapital(CompanyCapitalAccount $account): float
    {
        return (float) $account->transactions()
            ->where('type', CompanyCapitalTransactionType::CapitalAdded->value)
            ->where('available', false)
            ->sum('amount');
    }

    private function lentOut(CompanyCapitalAccount $account): float
    {
        $loanSum = (float) $account->transactions()
            ->whereIn('type', [
                CompanyCapitalTransactionType::LoanIssued->value,
                CompanyCapitalTransactionType::LoanRepayment->value,
                CompanyCapitalTransactionType::LoanCancelled->value,
            ])
            ->sum('amount');

        return round(-$loanSum, 2);
    }

    private function activeAccountForUpdate(): CompanyCapitalAccount
    {
        $account = CompanyCapitalAccount::query()->where('code', 'MAIN')->lockForUpdate()->first();
        if (! $account || ! $account->is_active || ! $account->transactions()->exists()) {
            $this->reject('capital', 'Configure opening company capital before recording loans.');
        }

        return $account;
    }

    private function recordLocked(
        CompanyCapitalAccount $account,
        CompanyCapitalTransactionType $type,
        float $amount,
        string $transactionDate,
        ?Model $reference,
        string $description,
        ?User $user,
        bool $available = true,
    ): CompanyCapitalTransaction {
        $lastId = (int) (CompanyCapitalTransaction::query()->max('id') ?? 0) + 1;

        return CompanyCapitalTransaction::create([
            'company_capital_account_id' => $account->id,
            'transaction_code' => 'CAP-'.str_pad((string) $lastId, 6, '0', STR_PAD_LEFT),
            'type' => $type,
            'amount' => round($amount, 2),
            'available' => $available,
            'transaction_date' => $transactionDate,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'description' => $description,
            'created_by' => $user?->id,
            'created_at' => now(),
        ]);
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2);
    }
}
