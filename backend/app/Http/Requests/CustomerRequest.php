<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
public function authorize(): bool
{
    return true;
}

public function rules(): array
{
    return [

        'name'=>'required|max:255',

        'phone'=>'nullable|max:30',

        'email'=>'nullable|email',

        'company'=>'nullable|max:255',

        'address'=>'nullable',

        'notes'=>'nullable'

    ];
}
}
