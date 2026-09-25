<?php

namespace App\Http\Requests\RoyaltyStatement;

use App\Models\RoyaltyStatement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoyaltyStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by RoyaltyStatementPolicy
    }

    public function rules(): array
    {
        return [
            'royalty_rate' => ['sometimes', 'numeric', 'between:0,100'],
            'status' => ['sometimes', 'string', Rule::in(RoyaltyStatement::STATUSES)],
            'issued_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'regenerate' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $statement = $this->route('royaltyStatement');
            if (! $statement) {
                return;
            }

            $newStatus = $this->input('status');

            // Cannot move an issued statement back to draft.
            if (
                $newStatus === RoyaltyStatement::STATUS_DRAFT
                && $statement->status !== RoyaltyStatement::STATUS_DRAFT
            ) {
                $v->errors()->add(
                    'status',
                    'An issued statement cannot be moved back to draft.'
                );
            }
        });
    }
}