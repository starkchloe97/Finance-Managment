<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Helpers\NumberGenerator;
use App\Models\Estimate;
use App\Models\TransportJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns an accepted estimate into a job.
 *
 * The estimate already holds both sides of the deal, so the job simply takes a
 * copy of them: what we charge, what we expect it to cost, and the profit
 * between the two. They are copied rather than read through the relation so a
 * later edit to the estimate cannot quietly rewrite a job already under way.
 */
class TransportJobService
{
    public function __construct(
        private ActivityService $activity
    ) {}

    /**
     * Which stage may follow which. A job moves one step at a time and never
     * backwards, so what the status says has actually happened. 'completed' is
     * the end of the road and leads nowhere.
     *
     * This is the only copy that decides anything — the dropdown on the job
     * page mirrors it, but a request that arrives by any other route is
     * checked here.
     */
    private const TRANSITIONS = [
        'draft' => ['confirmed'],
        'confirmed' => ['assigned'],
        'assigned' => ['in_transit'],
        'in_transit' => ['delivered'],
        'delivered' => ['completed'],
        'completed' => [],
    ];

    public function convert(Estimate $estimate)
    {
        return DB::transaction(function () use ($estimate) {

            // A refused conversion is a business rule, not a server fault, so it
            // answers 422 like every other rejected write rather than 500.
            if ($estimate->transportJob) {
                throw ValidationException::withMessages([
                    'estimate' => 'This estimate has already been converted to a job.',
                ]);
            }

            $estimate->update(['status' => 'accepted']);

            $job = TransportJob::create([
                'code' => NumberGenerator::generate('JOB', TransportJob::class),
                'estimate_id' => $estimate->id,
                'customer_id' => $estimate->customer_id,
                'job_date' => now(),
                'sell_price' => $estimate->estimated_sell,
                'cost_price' => $estimate->estimated_cost,
                'base_profit' => $estimate->estimated_profit,
                'extra_costs' => 0,
                'final_profit' => $estimate->estimated_profit,
                'status' => 'draft',
            ]);

            $this->activity->log(
                $job,
                ActivityEvent::JobCreated,
                "Job created from estimate {$estimate->code}",
                null,
                [
                    'code' => $job->code,
                    'sell_price' => $job->sell_price,
                    'cost_price' => $job->cost_price,
                    'base_profit' => $job->base_profit,
                ]
            );

            return $job->load('customer', 'estimate', 'expenses');

        });
    }

    /**
     * Advance a job to the next stage of the work.
     */
    public function changeStatus(TransportJob $job, string $status): TransportJob
    {
        $current = $job->status->value;

        if (! in_array($status, self::TRANSITIONS[$current] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition from {$current} to {$status}.",
            ]);
        }

        $job->update(['status' => $status]);

        $this->activity->log(
            $job,
            ActivityEvent::StatusChanged,
            "Status moved from {$current} to {$status}",
            ['status' => $current],
            ['status' => $status]
        );

        return $job->load('customer', 'estimate.items', 'expenses');
    }

    /**
     * Working notes for whoever is running the job. Not part of the deal and
     * never shown to the customer, so nothing is validated beyond it being
     * text — and nothing downstream reads it.
     */
    public function updateNotes(TransportJob $job, ?string $notes): TransportJob
    {
        $before = $job->internal_notes;

        if ($before === $notes) {
            return $job->load('customer', 'estimate.items', 'expenses');
        }

        $job->update(['internal_notes' => $notes]);

        $this->activity->log(
            $job,
            ActivityEvent::NotesUpdated,
            $this->notesDescription($before, $notes),
            ['internal_notes' => $before],
            ['internal_notes' => $notes]
        );

        return $job->load('customer', 'estimate.items', 'expenses');
    }

    private function notesDescription(?string $before, ?string $after): string
    {
        if ($before === null) {
            return 'Internal notes added';
        }

        return $after === null ? 'Internal notes cleared' : 'Internal notes updated';
    }
}
