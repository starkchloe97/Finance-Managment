<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\QueryRequest;
use App\Http\Resources\QueryResource;
use App\Models\Query;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    public function index(Request $request)
    {
        $queries = Query::query();

        if ($search = $request->input('search')) {
            $queries->where(function ($grouped) use ($search) {
                $grouped->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('service', 'like', "%{$search}%")
                    ->orWhere('pickup_location', 'like', "%{$search}%")
                    ->orWhere('dropoff_location', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $queries->where('status', $status);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        return QueryResource::collection(
            $queries->latest()->paginate($perPage)
        );
    }

    public function store(QueryRequest $request)
    {
        return (new QueryResource(
            Query::create($request->validated())
        ))->response()->setStatusCode(201);
    }

    public function show(Query $query)
    {
        return new QueryResource($query);
    }

    public function update(QueryRequest $request, Query $query)
    {
        $query->update($request->validated());

        return new QueryResource($query->refresh());
    }

    public function destroy(Query $query)
    {
        $query->delete();

        return response()->json([
            'message' => 'Query deleted.',
        ]);
    }
}
