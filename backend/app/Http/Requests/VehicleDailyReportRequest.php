<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleDailyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_public_holiday')) {
            $this->merge([
                'is_public_holiday' => $this->boolean('is_public_holiday'),
            ]);
        }

        if ($this->has('is_weekly_off')) {
            $this->merge([
                'is_weekly_off' => $this->boolean('is_weekly_off'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'report_date' => [
                'required',
                'date',
                Rule::unique('vehicle_daily_reports')
                    ->where(
                        'contract_vehicle_id',
                        $this->route('contractVehicle')->id
                    )
                    ->ignore($this->route('dailyReport')?->id),
            ],

            'time_in' => [
                'nullable',
                'date_format:H:i',
                'required_with:time_out',
            ],

            'time_out' => [
                'nullable',
                'date_format:H:i',
                'required_with:time_in',
                'after:time_in',
            ],

            'meter_in' => [
                'nullable',
                'numeric',
                'min:0',
                'required_with:meter_out',
            ],

            'meter_out' => [
                'nullable',
                'numeric',
                'min:0',
                'required_with:meter_in',
                'gt:meter_in',
            ],

            'fuel_drawn' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'is_public_holiday' => [
                'boolean',
            ],

            'is_weekly_off' => [
                'boolean',
            ],

            'status' => [
                'nullable',
                'in:draft,approved,rejected',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],
        ];
    }
}
