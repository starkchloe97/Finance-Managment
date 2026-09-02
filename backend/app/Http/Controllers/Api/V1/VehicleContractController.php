<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleContractRequest;
use App\Http\Resources\VehicleContractResource;
use App\Models\VehicleContract;
use App\Services\VehicleContractService;
use Illuminate\Http\Request;

class VehicleContractController extends Controller
{
    public function __construct(
        private VehicleContractService $service
    ) {}

    public function index(Request $request)
    {
        return VehicleContractResource::collection(
            $this->service->paginate(
                $request->only([
                    'search',
                    'status',
                    'per_page',
                ])
            )
        );
    }

    public function store(VehicleContractRequest $request)
    {
        $contract = $this->service->create(
            $request->validated()
        );

        return new VehicleContractResource(
            $contract->load('vehicles')
        );
    }

    public function show(VehicleContract $vehicleContract)
    {
        $vehicleContract->load('vehicles');

        return new VehicleContractResource(
            $vehicleContract
        );
    }

    public function update(
        VehicleContractRequest $request,
        VehicleContract $vehicleContract
    ) {
        return new VehicleContractResource(
            $this->service->update(
                $vehicleContract,
                $request->validated()
            )
        );
    }

    public function destroy(VehicleContract $vehicleContract)
    {
        $this->service->delete($vehicleContract);

        return response()->json([
            'message' => 'Vehicle contract deleted.',
        ]);
    }
}
