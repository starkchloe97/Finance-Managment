<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardRequest;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\TransportJob;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function dashboard(DashboardRequest $request): JsonResponse
    {
        [$from, $to] = $this->period($request);
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $periodDays = $from->diffInDays($to) + 1;
        $previousFrom = $from->copy()->subDays($periodDays);
        $previousTo = $from->copy()->subDay();

        $jobs = TransportJob::query()->whereBetween('job_date', [$fromDate, $toDate]);
        $totals = (clone $jobs)->selectRaw(
            'count(*) as jobs,
            coalesce(sum(sell_price), 0) as revenue,
            coalesce(sum(cost_price), 0) as planned_cost,
            coalesce(sum(cost_price + extra_costs), 0) as actual_cost,
            coalesce(sum(extra_costs), 0) as extra_costs,
            coalesce(sum(final_profit), 0) as profit'
        )->first();

        $previousJobs = TransportJob::query()->whereBetween('job_date', [
            $previousFrom->toDateString(),
            $previousTo->toDateString(),
        ]);
        $previous = (clone $previousJobs)->selectRaw(
            'coalesce(sum(sell_price), 0) as revenue,
            coalesce(sum(cost_price), 0) as planned_cost,
            coalesce(sum(cost_price + extra_costs), 0) as actual_cost,
            coalesce(sum(extra_costs), 0) as extra_costs,
            coalesce(sum(final_profit), 0) as profit'
        )->first();

        $revenue = (float) $totals->revenue;
        $profit = (float) $totals->profit;
        $previousRevenue = (float) $previous->revenue;
        $previousProfit = (float) $previous->profit;

        return response()->json([
            'meta' => [
                'period' => $request->validated('period') ?: 'this_month',
                'from' => $fromDate,
                'to' => $toDate,
                'previous_from' => $previousFrom->toDateString(),
                'previous_to' => $previousTo->toDateString(),
                'generated_at' => now()->toISOString(),
                'scopes' => [
                    'kpis' => 'selected_period',
                    'financial_overview' => 'selected_period',
                    'current_pipeline' => 'all_jobs',
                    'pending_estimates' => 'open_estimates',
                    'recent_jobs' => 'latest_jobs',
                ],
            ],
            'kpis' => [
                'revenue' => ['value' => $totals->revenue, 'previous' => $previous->revenue],
                'cost' => ['value' => $totals->planned_cost, 'previous' => $previous->planned_cost],
                'planned_cost' => ['value' => $totals->planned_cost, 'previous' => $previous->planned_cost],
                'actual_cost' => ['value' => $totals->actual_cost, 'previous' => $previous->actual_cost],
                'extra_costs' => ['value' => $totals->extra_costs, 'previous' => $previous->extra_costs],
                'profit' => ['value' => $totals->profit, 'previous' => $previous->profit],
                'profit_margin' => [
                    'value' => $this->margin($profit, $revenue),
                    'previous' => $this->margin($previousProfit, $previousRevenue),
                ],
                'active_jobs' => [
                    'value' => (clone $jobs)->whereNotIn('status', ['completed'])->count(),
                    'previous' => (clone $previousJobs)->whereNotIn('status', ['completed'])->count(),
                ],
            ],
            'financial_overview' => $this->financialOverview($jobs, $from, $to),
            'job_status' => (clone $jobs)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'current_pipeline' => TransportJob::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'recent_jobs' => TransportJob::with('customer')->latest('job_date')->limit(6)->get(),
            'pending_estimates' => Estimate::with('customer')
                ->whereIn('status', ['draft', 'sent'])
                ->latest('estimate_date')
                ->limit(5)
                ->get(),
            'alerts' => $this->alerts(),
        ]);
    }

    public function summary(): JsonResponse
    {
        $totals = TransportJob::selectRaw(
            'count(*) as jobs,
            coalesce(sum(sell_price), 0) as sell_price,
            coalesce(sum(cost_price), 0) as cost_price,
            coalesce(sum(base_profit), 0) as base_profit,
            coalesce(sum(extra_costs), 0) as extra_costs,
            coalesce(sum(final_profit), 0) as final_profit'
        )->first();

        return response()->json([
            'customers' => Customer::count(),
            'estimates' => Estimate::count(),
            'jobs' => $totals->jobs,
            'sell_price' => $totals->sell_price,
            'cost_price' => $totals->cost_price,
            'base_profit' => $totals->base_profit,
            'extra_costs' => $totals->extra_costs,
            'final_profit' => $totals->final_profit,
        ]);
    }

    private function period(DashboardRequest $request): array
    {
        $period = $request->validated('period') ?: 'this_month';

        if ($period === 'custom') {
            return [
                Carbon::createFromFormat('Y-m-d', $request->validated('from'))->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $request->validated('to'))->endOfDay(),
            ];
        }

        $today = today();

        return match ($period) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'this_week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_quarter' => [$today->copy()->startOfQuarter(), $today->copy()->endOfQuarter()],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };
    }

    private function financialOverview($jobs, Carbon $from, Carbon $to): array
    {
        $bucketByDay = $from->diffInDays($to) < 62;

        return $jobs->get([
            'job_date',
            'sell_price',
            'cost_price',
            'extra_costs',
            'final_profit',
        ])->groupBy(function (TransportJob $job) use ($bucketByDay) {
            return Carbon::parse($job->job_date)->format($bucketByDay ? 'Y-m-d' : 'Y-m');
        })->map(function ($group, string $period) {
            return [
                'period' => $period,
                'revenue' => round($group->sum(fn (TransportJob $job) => (float) $job->sell_price), 2),
                'cost' => round($group->sum(fn (TransportJob $job) => (float) $job->cost_price), 2),
                'planned_cost' => round($group->sum(fn (TransportJob $job) => (float) $job->cost_price), 2),
                'actual_cost' => round($group->sum(fn (TransportJob $job) => (float) $job->cost_price + (float) $job->extra_costs), 2),
                'extra_costs' => round($group->sum(fn (TransportJob $job) => (float) $job->extra_costs), 2),
                'profit' => round($group->sum(fn (TransportJob $job) => (float) $job->final_profit), 2),
            ];
        })->values()->all();
    }

    private function alerts(): array
    {
        $alerts = [];
        $today = today();

        TransportJob::query()
            ->with('customer')
            ->where(function ($query) {
                $query->where('final_profit', '<', 0)->orWhere('extra_costs', '>', 0);
            })
            ->latest('job_date')
            ->limit(10)
            ->get()
            ->each(function (TransportJob $job) use (&$alerts) {
                $isLoss = (float) $job->final_profit < 0;
                $alerts[] = [
                    'type' => 'job',
                    'id' => $job->id,
                    'code' => $job->code,
                    'title' => $isLoss ? 'Job is running at a loss' : 'Unexpected costs recorded',
                    'description' => $isLoss
                        ? "{$job->code} has a final loss of ".number_format(abs((float) $job->final_profit), 2).'.'
                        : "{$job->code} has ".number_format((float) $job->extra_costs, 2).' in unexpected costs.',
                    'severity' => $isLoss ? 'danger' : 'warning',
                    'href' => "/jobs/{$job->id}",
                ];
            });

        Estimate::query()
            ->with('customer')
            ->whereIn('status', ['draft', 'sent'])
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<=', $today->copy()->addDays(7))
            ->latest('valid_until')
            ->limit(10)
            ->get()
            ->each(function (Estimate $estimate) use (&$alerts, $today) {
                $expired = $estimate->valid_until->isBefore($today);
                $alerts[] = [
                    'type' => 'estimate',
                    'id' => $estimate->id,
                    'code' => $estimate->code,
                    'title' => $expired ? 'Estimate has expired' : 'Estimate expires soon',
                    'description' => $expired
                        ? "{$estimate->code} expired on {$estimate->valid_until->toDateString()}."
                        : "{$estimate->code} expires on {$estimate->valid_until->toDateString()}.",
                    'severity' => $expired ? 'danger' : 'warning',
                    'href' => "/estimates/{$estimate->id}",
                ];
            });

        return array_slice($alerts, 0, 10);
    }

    private function margin(float $profit, float $revenue): float
    {
        return $revenue === 0.0 ? 0.0 : round(($profit / $revenue) * 100, 1);
    }
}
