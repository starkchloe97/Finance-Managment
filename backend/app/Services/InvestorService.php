<?php

namespace App\Services;

use App\Helpers\NumberGenerator;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Loan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestorService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Investor::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('investor_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Investor
    {
        return DB::transaction(function () use ($data) {
            $data['investor_code'] = NumberGenerator::generate(
                'INV',
                Investor::class
            );

            return Investor::create($data);
        });
    }

    public function update(Investor $investor, array $data): Investor
    {
        return DB::transaction(function () use ($investor, $data) {
            $investor->update($data);

            return $investor->refresh();
        });
    }

    public function delete(Investor $investor): void
    {
        if (Investment::withTrashed()->where('investor_id', $investor->id)->exists()) {
            throw ValidationException::withMessages([
                'investor' => ['An investor with investment records cannot be deleted.'],
            ]);
        }

        if (Loan::query()->where('investor_id', $investor->id)->exists()) {
            throw ValidationException::withMessages([
                'investor' => ['An investor with loan records cannot be deleted.'],
            ]);
        }

        DB::transaction(function () use ($investor) {
            $investor->delete();
        });
    }
}
