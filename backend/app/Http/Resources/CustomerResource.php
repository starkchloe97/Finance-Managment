<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
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

            'created_at' => $this->created_at,

        ];
    }
}
