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
        return response()->json([
            'customers' => Customer::count(),
            'estimates' => Estimate::count(),
            'jobs' => TransportJob::count(),
            'sell_price' => TransportJob::sum('sell_price'),
            'cost_price' => TransportJob::sum('cost_price'),
            'base_profit' => TransportJob::sum('base_profit'),
            'extra_costs' => TransportJob::sum('extra_costs'),
            'final_profit' => TransportJob::sum('final_profit'),
        ]);
    }
}
