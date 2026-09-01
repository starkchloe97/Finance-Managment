<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstimateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,

            'customer_id' => $this->customer_id,

            'estimate_date' => $this->estimate_date,
            'valid_until' => $this->valid_until,

            'pickup' => $this->pickup,
            'destination' => $this->destination,
            'service_type' => $this->service_type,

            'estimated_cost' => $this->estimated_cost,
            'estimated_sell' => $this->estimated_sell,
            'estimated_profit' => $this->estimated_profit,

            'status' => $this->status,
            'remarks' => $this->remarks,

            'customer' => $this->whenLoaded('customer'),

            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,

                        'title' => $item->title,
                        'category' => $item->category,
                        'quantity' => $item->quantity,

                        'cost_price' => $item->cost_price,
                        'sell_price' => $item->sell_price,

                        'cost_total' => $item->cost_total,
                        'sell_total' => $item->sell_total,
                        'profit' => $item->profit,

                        'remarks' => $item->remarks,

                        'vehicles' => $item->relationLoaded('vehicles') ? $item->vehicles->map(function ($vehicle) {
                            return [
                                'id' => $vehicle->id,

                                'source' => $vehicle->source,
                                'asset_id' => $vehicle->asset_id,

                                'vehicle_name' => $vehicle->vehicle_name,
                                'make' => $vehicle->make,
                                'model' => $vehicle->model,
                                'model_year' => $vehicle->model_year,

                                'registration_number' =>
                                    $vehicle->registration_number,

                                'vin' => $vehicle->vin,
                                'engine_number' => $vehicle->engine_number,
                                'vehicle_type' => $vehicle->vehicle_type,
                                'color' => $vehicle->color,

                                'notes' => $vehicle->notes,

                                'asset' => $vehicle->relationLoaded('asset')
                                    ? ($vehicle->asset ? [
                                        'id' => $vehicle->asset->id,
                                        'asset_code' => $vehicle->asset->asset_code,
                                        'name' => $vehicle->asset->name,
                                        'make' => $vehicle->asset->make,
                                        'model' => $vehicle->asset->model,
                                        'model_year' => $vehicle->asset->model_year,
                                        'registration_number' =>
                                            $vehicle->asset->registration_number,
                                        'vehicle_type' =>
                                            $vehicle->asset->vehicle_type,
                                        'color' =>
                                            $vehicle->asset->color,
                                    ] : null)
                                    : null,
                            ];
                        }) : null,
                    ];
                });
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}