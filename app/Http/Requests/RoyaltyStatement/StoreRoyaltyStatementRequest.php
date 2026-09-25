<?php

namespace App\Http\Requests\RoyaltyStatement;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoyaltyStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by RoyaltyStatementPolicy
    }

    public function rules(): array
    {
        return [
            'artist_id' => ['required', 'integer', 'exists:artists,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'currency' => ['required', 'string', 'size:3'],
            'royalty_rate' => ['nullable', 'numeric', 'between:0,100'],
            'generate' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}