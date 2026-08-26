<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $summary = $this->jobs_summary;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'company' => $this->company,
            'address' => $this->address,
            'notes' => $this->notes,
            'job_count' => $summary['job_count'],
            'revenue' => $summary['revenue'],
            'cost' => $summary['cost'],
            'profit' => $summary['profit'],
            'estimate_count' => $this->estimate_count,
            'estimates' => EstimateResource::collection($this->whenLoaded('estimates')),
            'jobs' => $this->whenLoaded('jobs', fn () => $this->jobs->map(fn ($job) => [
                'id' => $job->id,
                'code' => $job->code,
                'job_date' => $job->job_date?->format('Y-m-d'),
                'status' => $job->status->value,
                'sell_price' => $job->sell_price,
                'cost_price' => $job->cost_price,
                'extra_costs' => $job->extra_costs,
                'final_profit' => $job->final_profit,
            ])->values()),
            'activities' => TransportJobActivityResource::collection($this->whenLoaded('activities')),
            'created_at' => $this->created_at,
        ];
    }
}
