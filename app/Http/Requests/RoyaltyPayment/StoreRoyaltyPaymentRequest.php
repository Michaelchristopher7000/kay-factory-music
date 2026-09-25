<?php

namespace App\Http\Requests\RoyaltyPayment;

use App\Models\RoyaltyPayment;
use App\Models\RoyaltyStatement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoyaltyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by RoyaltyPaymentPolicy
    }

    public function rules(): array
    {
        return [
            'statement_id' => ['required', 'integer', 'exists:royalty_statements,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'paid_at' => ['required', 'date'],
            'method' => ['required', 'string', Rule::in(RoyaltyPayment::METHODS)],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $statementId = $this->input('statement_id');
            $currency = $this->input('currency');

            if (! $statementId || ! $currency) {
                return;
            }

            $statement = RoyaltyStatement::find($statementId);

            if (! $statement) {
                return;
            }

            if (strtoupper($currency) !== strtoupper($statement->currency)) {
                $v->errors()->add(
                    'currency',
                    'Payment currency must match the statement currency (' . $statement->currency . ').'
                );
            }
        });
    }
}