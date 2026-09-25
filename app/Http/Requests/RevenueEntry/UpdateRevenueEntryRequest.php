<?php

namespace App\Http\Requests\RevenueEntry;

use App\Models\RevenueEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRevenueEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by RevenueEntryPolicy
    }

    public function rules(): array
    {
        return [
            'source' => ['sometimes', 'string', Rule::in(RevenueEntry::SOURCES)],
            'platform' => ['nullable', 'string', 'max:40'],
            'artist_id' => ['nullable', 'integer', 'exists:artists,id'],
            'release_id' => ['nullable', 'integer', 'exists:releases,id'],
            'track_id' => ['nullable', 'integer', 'exists:tracks,id'],
            'distribution_id' => ['nullable', 'integer', 'exists:distributions,id'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'received_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}