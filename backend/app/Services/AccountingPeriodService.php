<?php

namespace App\Services;

use App\Models\AccountingPeriodClose;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingPeriodService
{
    public function history(): Collection
    {
        return AccountingPeriodClose::query()
            ->with('closer:id,name')
            ->latest('period_end')
            ->get();
    }

    public function close(
        string $type,
        string $label,
        ?int $userId,
        FinancialReportService $reports,
    ): AccountingPeriodClose {
        [$start, $end] = $this->dates($type, $label);
        if ($end->greaterThanOrEqualTo(today())) {
            throw ValidationException::withMessages([
                'period_label' => 'Only fully elapsed accounting periods can be closed.',
            ]);
        }

        return DB::transaction(function () use ($type, $label, $start, $end, $userId, $reports) {
            if (AccountingPeriodClose::query()
                ->where('period_type', $type)
                ->where('period_label', $label)
                ->exists()) {
                throw ValidationException::withMessages([
                    'period_label' => 'This accounting period has already been closed.',
                ]);
            }

            $close = AccountingPeriodClose::query()->create([
                'period_type' => $type,
                'period_label' => $label,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'closed_by' => $userId,
                'closed_at' => now(),
                'summary' => [
                    'generated_at' => now()->toISOString(),
                    'report' => $reports->build($start->toDateString(), $end->toDateString()),
                ],
            ]);

            return $close->load('closer:id,name');
        });
    }

    public function assertOpen(?string $date): void
    {
        if (! $date) {
            return;
        }

        $closed = AccountingPeriodClose::query()
            ->whereDate('period_start', '<=', $date)
            ->whereDate('period_end', '>=', $date)
            ->exists();

        if ($closed) {
            throw ValidationException::withMessages([
                'date' => "Financial changes dated {$date} are not allowed because that accounting period is closed.",
            ]);
        }
    }

    private function dates(string $type, string $label): array
    {
        $valid = $type === 'month'
            ? preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $label) === 1
            : ($type === 'year' && preg_match('/^\d{4}$/', $label) === 1);

        if (! $valid) {
            throw ValidationException::withMessages([
                'period_label' => 'Choose a valid month (YYYY-MM) or year (YYYY).',
            ]);
        }

        $start = $type === 'month'
            ? Carbon::createFromFormat('!Y-m', $label)->startOfMonth()
            : Carbon::createFromFormat('!Y', $label)->startOfYear();

        return [$start, $start->copy()->endOf($type)];
    }
}
