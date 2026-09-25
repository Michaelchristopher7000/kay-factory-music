<?php

namespace App\Http\Requests\Distribution;

use App\Models\Distribution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by DistributionPolicy
    }

    public function rules(): array
    {
        $releaseId = $this->input('release_id');
        $platform = $this->input('platform');

        return [
            'release_id' => [
                'required', 'integer', 'exists:releases,id',
                Rule::unique('distributions', 'release_id')
                    ->where(fn ($q) => $q->where('platform', $platform)),
            ],
            'platform' => ['required', 'string', Rule::in(Distribution::PLATFORMS)],
            'status' => ['sometimes', 'string', Rule::in(Distribution::STATUSES)],
            'distributor' => ['nullable', 'string', 'max:100'],
            'territory' => ['nullable', 'string', 'max:40'],
            'scheduled_for' => ['nullable', 'date'],
            'submitted_at' => ['nullable', 'date'],
            'live_at' => ['nullable', 'date'],
            'takedown_at' => ['nullable', 'date'],
            'platform_release_id' => ['nullable', 'string', 'max:100'],
            'platform_url' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'release_id.unique' => 'This release already has a distribution record for that platform.',
        ];
    }
}