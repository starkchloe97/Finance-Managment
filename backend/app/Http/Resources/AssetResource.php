<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'asset_code' => $this->asset_code,

            'asset_type' => $this->asset_type?->value,
            'asset_type_label' => $this->asset_type?->label(),

            'name' => $this->name,

            'make' => $this->make,
            'model' => $this->model,
            'model_year' => $this->model_year,

            'registration_number' =>
                $this->registration_number,

            'vin' => $this->vin,

            'engine_number' =>
                $this->engine_number,

            'vehicle_type' =>
                $this->vehicle_type,

            'color' => $this->color,

            'purchase_date' =>
                $this->purchase_date?->toDateString(),

            'purchase_price' =>
                $this->purchase_price,

            'current_value' =>
                $this->current_value,

            'status' =>
                $this->status?->value,

            'status_label' =>
                $this->status?->label(),

            'notes' => $this->notes,

            'created_by' => $this->created_by,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}