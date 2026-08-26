<?php

namespace App\Services;

use App\Models\LoanBorrower;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LoanBorrowerService
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return LoanBorrower::query()
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('borrower_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(100);
    }

    public function create(array $data): LoanBorrower
    {
        $lastId = (int) (LoanBorrower::query()->max('id') ?? 0) + 1;

        return LoanBorrower::create([
            ...$data,
            'borrower_code' => 'BOR-'.str_pad((string) $lastId, 6, '0', STR_PAD_LEFT),
        ]);
    }
}
