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
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'internal_notes' => $this->internal_notes,
        ];
    }
}
