<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'investment_id' => $this->investment_id, 'transport_job_id' => $this->transport_job_id, 'amount' => $this->amount, 'status' => $this->status->value, 'allocated_at' => $this->allocated_at?->toISOString(), 'notes' => $this->notes, 'investment' => $this->whenLoaded('investment', fn () => (new InvestmentResource($this->investment))->resolve()), 'job' => $this->whenLoaded('transportJob', fn () => ['id' => $this->transportJob->id, 'code' => $this->transportJob->code, 'status' => $this->transportJob->status->value])];
    }
}
