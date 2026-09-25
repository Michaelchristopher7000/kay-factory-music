<?php

namespace App\Http\Requests\RoyaltyPayment;

use App\Models\RoyaltyPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoyaltyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by RoyaltyPaymentPolicy
    }

    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'paid_at' => ['sometimes', 'date'],
            'method' => ['sometimes', 'string', Rule::in(RoyaltyPayment::METHODS)],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}