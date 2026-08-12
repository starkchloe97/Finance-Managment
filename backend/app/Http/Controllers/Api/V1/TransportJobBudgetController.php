<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobBudgetRequest;
use App\Models\TransportJob as Job;
use App\Services\TransportJobBudgetService as JobBudgetService;
use Illuminate\Http\Request;

class TransportJobBudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
    StoreJobBudgetRequest $request,
    Job $job,
    JobBudgetService $service
    )
    {
        return response()->json(

            $service->update(
                $job,
                $request->validated()['items']
            )

        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
