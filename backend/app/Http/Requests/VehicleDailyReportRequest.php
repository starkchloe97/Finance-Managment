<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleDailyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_date' => [
                'required',
                'date',
            ],

            'time_in' => [
                'nullable',
                'date_format:H:i',
            ],

            'time_out' => [
                'nullable',
                'date_format:H:i',
            ],

            'meter_in' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'meter_out' => [
                'nullable',
                'numeric',
                'min:0',
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