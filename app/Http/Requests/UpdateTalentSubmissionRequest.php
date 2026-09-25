<?php

namespace App\Http\Requests;

use App\Models\TalentSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTalentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate handled in controller
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                Rule::in(TalentSubmission::STATUSES),
            ],
            'manager_notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'reviewed_by' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id',
            ],
        ];
    }
}