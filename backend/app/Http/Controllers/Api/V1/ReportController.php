<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\TransportJob;

class ReportController extends Controller
{
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
