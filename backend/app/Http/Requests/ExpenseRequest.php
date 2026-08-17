<?php

namespace App\Http\Requests;

use App\Enums\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Serves both adding and editing an unexpected cost — the rules are the same
 * either way, and the edit form is the add form.
 */
class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            // A cost is money spent. A negative one would quietly add profit
            // back and a zero one is not a cost at all, so both are refused
            // rather than interpreted.
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'Amount must be greater than 0.',
            'category.enum' => 'Choose one of the listed categories.',
        ];
    }
}
