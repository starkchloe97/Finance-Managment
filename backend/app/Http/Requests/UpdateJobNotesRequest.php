<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'internal_notes' => ['nullable', 'string'],
        ];
    }

    /**
     * An empty textarea means the notes were cleared, not that they were left
     * as an empty string.
     */
    protected function prepareForValidation(): void
    {
        $notes = $this->input('internal_notes');

        if (is_string($notes) && trim($notes) === '') {
            $this->merge(['internal_notes' => null]);
        }
    }
}
