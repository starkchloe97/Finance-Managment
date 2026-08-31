<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AssetService
{
    public function paginate(
        array $filters
    ): LengthAwarePaginator {
        return Asset::query()

            ->when(
                $filters['search'] ?? null,
                function ($query, $search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where(
                                'asset_code',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'make',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'model',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'registration_number',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'vin',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            )

            ->when(
                $filters['asset_type'] ?? null,
                fn ($query, $type) =>
                    $query->where(
                        'asset_type',
                        $type
                    )
            )

            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) =>
                    $query->where(
                        'status',
                        $status
                    )
            )

            ->latest()

            ->paginate(
                min(
                    max(
                        (int) (
                            $filters['per_page']
                            ?? 15
                        ),
                        1
                    ),
                    100
                )
            );
    }

    public function create(
        array $data,
        ?int $userId = null
    ): Asset {
        return Asset::create([
            ...$data,

            'asset_code' =>
                $this->nextAssetCode(),

            'created_by' =>
                $userId,
        ]);
    }

    public function update(
        Asset $asset,
        array $data
    ): Asset {
        $asset->update($data);

        return $asset->refresh();
    }

    public function delete(
        Asset $asset
    ): void {
        $asset->delete();
    }

    private function nextAssetCode(): string
    {
        $nextNumber =
            (int) (
                Asset::withTrashed()->max('id')
                ?? 0
            ) + 1;

        return 'AST-' . str_pad(
            (string) $nextNumber,
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}