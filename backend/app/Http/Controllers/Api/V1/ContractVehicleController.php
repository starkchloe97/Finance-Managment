<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractVehicleResource;
use App\Models\ContractVehicle;

class ContractVehicleController extends Controller
{
    public function show(
        ContractVehicle $contractVehicle
    ): ContractVehicleResource {
        return new ContractVehicleResource(
            $contractVehicle
        );
    }
}
