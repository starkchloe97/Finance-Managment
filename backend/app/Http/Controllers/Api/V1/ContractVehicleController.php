<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractVehicleResource;
use App\Models\ContractVehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContractVehicleController extends Controller
{
    public function index(
        Request $request
    ): AnonymousResourceCollection {
        $per_page = min(
            max((int) ($request->query('per_page') ?? 24), 1),
            100
        );

        $vehicles = ContractVehicle::query()
            ->with('contract')
            ->latest()
            ->paginate($per_page);

        return ContractVehicleResource::collection($vehicles);
    }

    public function show(
        Request $request,
        ContractVehicle $contractVehicle
    ): ContractVehicleResource {
        $contractVehicle->load('contract');

        return new ContractVehicleResource(
            $contractVehicle
        );
    }
}
