<?php

namespace App\Http\Requests\Expense;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by ExpensePolicy
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(Expense::CATEGORIES)],
            'artist_id' => ['nullable', 'integer', 'exists:artists,id'],
            'release_id' => ['nullable', 'integer', 'exists:releases,id'],
            'track_id' => ['nullable', 'integer', 'exists:tracks,id'],
            'distribution_id' => ['nullable', 'integer', 'exists:distributions,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'incurred_at' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date', 'after_or_equal:incurred_at'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}