<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentResource extends JsonResource
{
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
            'investment_category' => $this->investment_category?->value,
            'return_type' => $this->return_type?->value,
            'return_percentage' => $this->return_percentage,
            'fixed_return_amount' => $this->fixed_return_amount,
            'calculated_return_amount' => $this->calculated_return_amount,
            'allocated_amount' => $this->allocated_amount,
            'remaining_capital' => $this->remaining_capital,
            'period_months' => $this->period_months,
            'return_policy_days' => $this->return_policy_days,
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
            'expected_settlement_amount' => $this->expected_settlement_amount,
        ];
    }
}
