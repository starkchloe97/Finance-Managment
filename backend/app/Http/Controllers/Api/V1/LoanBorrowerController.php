<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoanBorrowerRequest;
use App\Http\Resources\LoanBorrowerResource;
use App\Services\LoanBorrowerService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LoanBorrowerController extends Controller
{
    public function __construct(private LoanBorrowerService $borrowers) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return LoanBorrowerResource::collection($this->borrowers->paginate($request->string('search')->toString() ?: null));
    }

    public function store(LoanBorrowerRequest $request): LoanBorrowerResource
    {
        return new LoanBorrowerResource($this->borrowers->create($request->validated()));
    }
}
