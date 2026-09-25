<?php

namespace App\Http\Requests\RoyaltyStatement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoyaltyStatementLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by RoyaltyStatementLinePolicy
    }

    public function rules(): array
    {
        return [
            'royalty_rate' => ['sometimes', 'numeric', 'between:0,100'],
            'royalty_amount' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }
}