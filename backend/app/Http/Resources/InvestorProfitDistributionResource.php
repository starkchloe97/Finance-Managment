<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorProfitDistributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'investment_id' => $this->investment_id, 'transport_job_id' => $this->transport_job_id, 'investor_id' => $this->investor_id, 'allocation_id' => $this->allocation_id, 'profit_basis' => $this->profit_basis, 'profit_share_value' => $this->profit_share_value, 'profit_amount' => $this->profit_amount, 'status' => $this->status->value, 'distributed_at' => $this->distributed_at?->toISOString(), 'notes' => $this->notes, 'investor' => $this->whenLoaded('investor', fn () => ['id' => $this->investor->id, 'name' => $this->investor->name, 'investor_code' => $this->investor->investor_code])];
    }
}
