<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\TransportJob;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function dashboard(Request $request)
    {
        [$from, $to] = $this->period($request->string('period')->toString());
        $jobs = TransportJob::query()->whereBetween('job_date', [$from, $to]);
        $totals = (clone $jobs)->selectRaw('count(*) as jobs, coalesce(sum(sell_price), 0) as revenue, coalesce(sum(cost_price), 0) as cost, coalesce(sum(final_profit), 0) as profit')->first();
        $previous = TransportJob::query()->whereBetween('job_date', [$from->copy()->subDays($from->diffInDays($to) + 1), $from->copy()->subDay()])->selectRaw('coalesce(sum(sell_price), 0) as revenue, coalesce(sum(cost_price), 0) as cost, coalesce(sum(final_profit), 0) as profit, count(*) as jobs')->first();

        return response()->json([
            'kpis' => [
                'revenue' => ['value' => $totals->revenue, 'previous' => $previous->revenue],
                'cost' => ['value' => $totals->cost, 'previous' => $previous->cost],
                'profit' => ['value' => $totals->profit, 'previous' => $previous->profit],
                'active_jobs' => ['value' => (clone $jobs)->whereNotIn('status', ['completed'])->count(), 'previous' => $previous->jobs],
            ],
            'financial_overview' => (clone $jobs)->selectRaw("date_format(job_date, '%Y-%m') as period, coalesce(sum(sell_price), 0) as revenue, coalesce(sum(cost_price), 0) as cost, coalesce(sum(final_profit), 0) as profit")->groupBy('period')->orderBy('period')->get(),
            'job_status' => (clone $jobs)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'recent_jobs' => TransportJob::with('customer')->latest('job_date')->limit(6)->get(),
            'pending_estimates' => Estimate::with('customer')->whereIn('status', ['draft', 'sent'])->latest('estimate_date')->limit(5)->get(),
            'alerts' => [],
        ]);
    }

    private function period(string $period): array
    {
        $today = today();
        return match ($period) {
            'today' => [$today, $today],
            'this_week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => [$today->copy()->startOfQuarter(), $today->copy()->endOfQuarter()],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };
    }
    /**
     * The whole business in one payload: how much work is on the books and
     * what it is earning once unexpected costs are taken off.
     */
    public function summary()
    {
        // One pass over transport_jobs for all five figures — the previous
        // version ran a separate aggregate query per column.
        $totals = TransportJob::selectRaw('
            count(*) as jobs,
            coalesce(sum(sell_price), 0) as sell_price,
            coalesce(sum(cost_price), 0) as cost_price,
            coalesce(sum(base_profit), 0) as base_profit,
            coalesce(sum(extra_costs), 0) as extra_costs,
            coalesce(sum(final_profit), 0) as final_profit
        ')->first();

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
}
