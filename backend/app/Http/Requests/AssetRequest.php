<?php

namespace App\Http\Requests;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $asset = $this->route('asset');

        $assetId = $asset instanceof \App\Models\Asset
            ? $asset->id
            : null;

        return [
            /*
             * General
             */
            'asset_type' => [
                'required',
                Rule::enum(AssetType::class),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            /*
             * Vehicle
             */
            'make' => [
                'nullable',
                'string',
                'max:100',
            ],

            'model' => [
                'nullable',
                'string',
                'max:100',
            ],

            'model_year' => [
                'nullable',
                'integer',
                'min:1900',
                'max:' . (date('Y') + 1),
            ],

            'registration_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('assets', 'registration_number')
                    ->ignore($assetId),
            ],

            'vin' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('assets', 'vin')
                    ->ignore($assetId),
            ],

            'engine_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('assets', 'engine_number')
                    ->ignore($assetId),
            ],

            'vehicle_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'color' => [
                'nullable',
                'string',
                'max:50',
            ],

            /*
             * Financial
             */
            'purchase_date' => [
                'nullable',
                'date',
            ],

            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'current_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
             * Status
             */
            'status' => [
                'required',
                Rule::enum(AssetStatus::class),
            ],

            /*
             * Notes
             */
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}