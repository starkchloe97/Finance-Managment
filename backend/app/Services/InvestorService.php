<?php

namespace App\Services;

use App\Helpers\NumberGenerator;
use App\Models\Investment;
use App\Models\Investor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestorService
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Investor::query()
            ->latest()
            ->paginate($perPage);
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

        DB::transaction(function () use ($investor) {
            $investor->delete();
        });
    }
}
