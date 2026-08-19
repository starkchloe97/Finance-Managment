<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'investment_code' => $this->investment_code,

            'investor_id' => $this->investor_id,

            'investor' => $this->whenLoaded(
                'investor',
                fn () => [
                    'id' => $this->investor->id,
                    'investor_code' => $this->investor->investor_code,
                    'name' => $this->investor->name,
                ]
            ),

            'investment_date' => $this->investment_date?->format('Y-m-d'),

            'amount' => $this->amount,

            'period_months' => $this->period_months,

            'return_policy_days' => $this->return_policy_days,

            'min_return_percent' => $this->min_return_percent,

            'max_return_percent' => $this->max_return_percent,

            'status' => $this->status->value,

            'matured_at' => $this->matured_at?->toISOString(),

            'withdrawn_at' => $this->withdrawn_at?->toISOString(),

            'settled_at' => $this->settled_at?->toISOString(),

            'cancelled_at' => $this->cancelled_at?->toISOString(),

            'deduction_amount' => $this->deduction_amount,

            'notes' => $this->notes,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

            'maturity_date' => $this->maturity_date?->toDateString(),

            'minimum_return_amount' => $this->minimum_return_amount,

            'maximum_return_amount' => $this->maximum_return_amount,

            'minimum_settlement_amount' => $this->minimum_settlement_amount,

            'maximum_settlement_amount' => $this->maximum_settlement_amount,
        ];
    }
}
