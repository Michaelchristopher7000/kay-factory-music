<?php

namespace Database\Seeders;

use App\Models\Distribution;
use App\Models\Release;
use App\Models\User;
use Illuminate\Database\Seeder;

class DistributionSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@kayfactory.test')->first();
        $labelManager = User::where('email', 'manager@kayfactory.test')->first();

        $creators = array_values(array_filter([$superAdmin, $labelManager]));

        if (empty($creators)) {
            return;
        }

        // Only distribute releases that have a real release_date in the past.
        $releases = Release::query()
            ->whereNotNull('release_date')
            ->whereDate('release_date', '<=', now())
            ->get();

        if ($releases->isEmpty()) {
            return;
        }

        // Platform pools per release type — EPs/albums go wider than singles.
        $corePlatforms = ['spotify', 'apple_music', 'youtube_music', 'boomplay'];
        $extendedPlatforms = [
            'spotify', 'apple_music', 'youtube_music', 'amazon_music',
            'deezer', 'tidal', 'audiomack', 'boomplay', 'soundcloud',
        ];

        $distributors = ['Empire', 'The Orchard', 'Believe', 'TuneCore'];

        foreach ($releases as $release) {
            $platforms = in_array($release->type, ['album', 'ep', 'mixtape'], true)
                ? $extendedPlatforms
                : $corePlatforms;

            foreach ($platforms as $platform) {
                $status = $this->statusFor($release->release_date);

                Distribution::updateOrCreate(
                    [
                        'release_id' => $release->id,
                        'platform' => $platform,
                    ],
                    [
                        'distributor' => fake()->randomElement($distributors),
                        'status' => $status,
                        'territory' => 'worldwide',
                        'scheduled_for' => $release->release_date?->format('Y-m-d'),
                        'submitted_at' => in_array($status, ['submitted', 'live', 'takedown'], true)
                            ? $release->release_date?->copy()->subDays(21)->format('Y-m-d')
                            : null,
                        'live_at' => $status === 'live'
                            ? $release->release_date?->format('Y-m-d')
                            : null,
                        'takedown_at' => null,
                        'platform_release_id' => $status === 'live'
                            ? strtoupper(substr($platform, 0, 3)) . '-' . str_pad((string) $release->id, 6, '0', STR_PAD_LEFT)
                            : null,
                        'platform_url' => null,
                        'notes' => null,
                        'created_by' => fake()->randomElement($creators)->id,
                    ]
                );
            }
        }
    }

    private function statusFor(?\Illuminate\Support\Carbon $releaseDate): string
    {
        if (! $releaseDate) {
            return 'pending';
        }

        // Released in the past → live.
        return 'live';
    }
}