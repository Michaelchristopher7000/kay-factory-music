<?php

namespace App\Http\Requests\Distribution;

use App\Models\Distribution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by DistributionPolicy
    }

    public function rules(): array
    {
        $distribution = $this->route('distribution');
        $distributionId = $distribution?->id;

        $targetReleaseId = $this->input('release_id') ?? $distribution?->release_id;
        $targetPlatform = $this->input('platform') ?? $distribution?->platform;

        return [
            'release_id' => [
                'sometimes', 'integer', 'exists:releases,id',
                Rule::unique('distributions', 'release_id')
                    ->where(fn ($q) => $q->where('platform', $targetPlatform))
                    ->ignore($distributionId),
            ],
            'platform' => ['sometimes', 'string', Rule::in(Distribution::PLATFORMS)],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $distribution = $this->route('distribution');
            if (! $distribution) {
                return;
            }

            $newReleaseId = $this->input('release_id');
            $newPlatform = $this->input('platform');

            // Prevent (release_id, platform) collision when only one of them is being changed.
            $finalReleaseId = $newReleaseId ?? $distribution->release_id;
            $finalPlatform = $newPlatform ?? $distribution->platform;

            $exists = Distribution::query()
                ->where('release_id', $finalReleaseId)
                ->where('platform', $finalPlatform)
                ->where('id', '!=', $distribution->id)
                ->exists();

            if ($exists) {
                $v->errors()->add(
                    'release_id',
                    'This release already has a distribution record for that platform.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'release_id.unique' => 'This release already has a distribution record for that platform.',
        ];
    }
}