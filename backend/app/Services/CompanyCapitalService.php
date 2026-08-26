<?php

namespace App\Services;

use App\Enums\CompanyCapitalTransactionType;
use App\Models\CompanyCapitalAccount;
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
            return ['initialized' => false, 'account' => null, 'opening_balance' => 0.0, 'current_balance' => 0.0, 'transactions' => []];
        }

        $transactions = $account->transactions()->latest('transaction_date')->latest('id')->limit(20)->get();

        return [
            'initialized' => $account->transactions()->exists(),
            'account' => $account,
            'opening_balance' => (float) $account->transactions()
                ->where('type', CompanyCapitalTransactionType::OpeningBalance->value)->sum('amount'),
            'current_balance' => (float) $account->transactions()->sum('amount'),
            'transactions' => $transactions,
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
            );

            return $this->snapshot();
        });
    }

    public function assertAvailable(float $amount): void
    {
        $account = $this->activeAccountForUpdate();
        $available = (float) $account->transactions()->sum('amount');
        if ($available < $amount) {
            $this->reject('amount', 'Loan amount exceeds available company capital.');
        }
    }

    public function record(
        CompanyCapitalTransactionType $type,
        float $amount,
        string $transactionDate,
        ?Model $reference,
        string $description,
        ?User $user,
    ): CompanyCapitalTransaction {
        $account = $this->activeAccountForUpdate();

        return $this->recordLocked($account, $type, $amount, $transactionDate, $reference, $description, $user);
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
    ): CompanyCapitalTransaction {
        $lastId = (int) (CompanyCapitalTransaction::query()->max('id') ?? 0) + 1;

        return CompanyCapitalTransaction::create([
            'company_capital_account_id' => $account->id,
            'transaction_code' => 'CAP-'.str_pad((string) $lastId, 6, '0', STR_PAD_LEFT),
            'type' => $type,
            'amount' => round($amount, 2),
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
}
