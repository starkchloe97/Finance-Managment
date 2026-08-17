<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransportJobActivityResource;
use App\Models\TransportJob;

class TransportJobActivityController extends Controller
{
    /**
     * A job's history, newest first.
     *
     * Not paginated: a job collects a handful of events over its life — a
     * creation, five status moves, a few costs — so the whole timeline is one
     * cheap read. If jobs ever start accruing hundreds of events, this is the
     * place that needs a limit.
     */
    public function index(TransportJob $job)
    {
        return TransportJobActivityResource::collection(
            $job->activities()
                ->with('author')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
        );
    }
}
