<?php

namespace App\Services;

use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;

class BankAccountService
{
    public function create(array $data): BankAccount
    {
        return DB::transaction(function () use ($data) {
            $account = BankAccount::create([
                ...$data,
                'current_balance' => $data['opening_balance'],
            ]);

            $account->transactions()->create([
                'type' => 'opening_balance',
                'amount' => $data['opening_balance'],
                'balance_after' => $data['opening_balance'],
                'description' => 'Opening balance',
                'transaction_date' => today(),
            ]);

            return $account;
        });
    }
}
