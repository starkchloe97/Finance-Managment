<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\TransportJob;
use App\Models\TransportJobExpense;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Expenses are the unexpected costs that turn up once a job is under way —
 * a breakdown, a delay, an extra permit. They are not part of the agreed cost
 * price, so each one is added to the job's extra_costs and comes off the
 * profit the job was taken on.
 *
 * extra_costs and final_profit are stored columns, not computed on read, so
 * every change here has to write them back through TransportJob::recalculate().
 * The write, the recalculation and the timeline entry go in one transaction:
 * a job whose expenses and totals disagree is worse than no change at all.
 */
class TransportJobExpenseService
{
    public function __construct(
        private ActivityService $activity
    ) {}

    public function add(TransportJob $job, array $data): TransportJobExpense
    {
        return DB::transaction(function () use ($job, $data) {

            $job = TransportJob::query()->lockForUpdate()->findOrFail($job->id);
            $this->ensureUnlocked($job);

            $expense = $job->expenses()->create($data);

            $job->recalculate();

            $this->activity->log(
                $job,
                ActivityEvent::CostAdded,
                "Unexpected cost added: {$expense->title} ({$this->amount($expense)})",
                null,
                $this->snapshot($expense)
            );

            return $expense;
        });
    }

    public function update(TransportJobExpense $expense, array $data): TransportJobExpense
    {
        return DB::transaction(function () use ($expense, $data) {

            $job = $expense->transportJob;
            $this->ensureUnlocked($job);

            // Both sides are kept whole rather than just the fields that moved,
            // so the timeline can show the change without needing the row it
            // describes to still exist.
            $before = $this->snapshot($expense);

            $expense->update($data);

            $job->recalculate();

            $this->activity->log(
                $job,
                ActivityEvent::CostUpdated,
                $this->describeChange($before, $expense),
                $before,
                $this->snapshot($expense->fresh())
            );

            return $expense;
        });
    }

    public function remove(TransportJobExpense $expense): void
    {
        DB::transaction(function () use ($expense) {

            $job = $expense->transportJob;
            $this->ensureUnlocked($job);

            // Read the expense before it goes, so the timeline can still say what
            // was removed.
            $snapshot = $this->snapshot($expense);
            $description = "Unexpected cost removed: {$expense->title} ({$this->amount($expense)})";

            $expense->delete();

            $job->recalculate();

            $this->activity->log($job, ActivityEvent::CostDeleted, $description, $snapshot, null);
        });
    }

    /**
     * The amount is what actually moves the profit, so when it changes the
     * timeline says so outright instead of a bare "was edited".
     */
    private function describeChange(array $before, TransportJobExpense $after): string
    {
        $title = $after->title;

        $was = (float) $before['amount'];
        $now = (float) $after->amount;

        if ($was === $now) {
            return "Unexpected cost updated: {$title}";
        }

        return "Unexpected cost updated: {$title} ("
            .number_format($was, 2).' → '.number_format($now, 2).')';
    }

    private function snapshot(TransportJobExpense $expense): array
    {
        return [
            'title' => $expense->title,
            'category' => $expense->category->value,
            'amount' => $expense->amount,
            'expense_date' => $expense->expense_date?->toDateString(),
            'notes' => $expense->notes,
        ];
    }

    private function amount(TransportJobExpense $expense): string
    {
        return number_format((float) $expense->amount, 2);
    }

    private function ensureUnlocked(TransportJob $job): void
    {
        if ($job->financially_locked_at) {
            throw ValidationException::withMessages(['job' => ['Financially locked jobs require an adjustment record for changes.']]);
        }
    }
}
