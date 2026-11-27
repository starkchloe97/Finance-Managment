<?php

namespace App\Services;

use App\Models\TransactionReceipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReceiptService
{
    public function issue(
        Model $source,
        string $type,
        string $direction,
        float $amount,
        string $date,
        string $description,
        ?string $partyName = null,
        ?string $notes = null,
        ?User $user = null,
    ): TransactionReceipt {
        $existing = TransactionReceipt::query()
            ->where('receiptable_type', $source->getMorphClass())
            ->where('receiptable_id', $source->getKey())
            ->first();

        if ($existing) {
            return $existing;
        }

        $nextNumber = (int) (TransactionReceipt::query()->max('id') ?? 0) + 1;

        return $source->receipt()->create([
            'receipt_no' => 'RCT-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT),
            'receipt_type' => $type,
            'direction' => $direction,
            'amount' => round($amount, 2),
            'receipt_date' => $date,
            'party_name' => $partyName,
            'description' => $description,
            'notes' => $notes,
            'created_by' => $user?->id,
        ]);
    }
}
