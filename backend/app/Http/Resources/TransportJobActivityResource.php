<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportJobActivityResource extends JsonResource
{
    /**
     * The timeline is read as a sentence — when, who, what — so the author is
     * flattened to a name rather than nesting a whole user. old_value and
     * new_value ride along for anything that wants the detail behind the
     * summary.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'event_type' => $this->event_type,

            'description' => $this->description,

            'old_value' => $this->old_value,

            'new_value' => $this->new_value,

            // Null when the change came from a seeder or console command.
            'author' => $this->author?->name,

            'created_at' => $this->created_at,

        ];
    }
}
