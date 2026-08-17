<?php

namespace App\Http\Requests;

use App\Enums\JobStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only checks that the target is a real stage. Whether it is a legal move
     * from where the job is now is the service's decision, not the form's.
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(JobStatus::class)],
        ];
    }
}
