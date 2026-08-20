<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportJobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * internal_notes is $hidden on the model, so it has to be asked for. This
     * is the job page's own resource and the one place the notes are meant to
     * be read — everywhere else a job gets serialised, they stay out.
     *
     * actual_cost and margin are derived from the job's stored figures, not
     * recomputed here: actual cost is the planned cost plus the unexpected
     * total, and margin is final profit over the customer's price. The frontend
     * renders these values; it never calculates them.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'internal_notes' => $this->internal_notes,
            'financially_locked_at' => $this->financially_locked_at?->toISOString(),
            'expense_total' => $this->whenLoaded('expenses', fn () => round($this->expenses->sum('amount'), 2)),
            'actual_cost' => round((float) $this->cost_price + (float) $this->extra_costs, 2),
            'margin' => $this->sell_price != 0
                ? round(((float) $this->final_profit / (float) $this->sell_price) * 100, 1)
                : 0,
            'financial_adjustments' => FinancialAdjustmentResource::collection(
                $this->whenLoaded('financialAdjustments')
            ),
        ];
    }
}
