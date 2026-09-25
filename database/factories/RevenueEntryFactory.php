<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\Distribution;
use App\Models\Release;
use App\Models\RevenueEntry;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RevenueEntry>
 */
class RevenueEntryFactory extends Factory
{
    protected $model = RevenueEntry::class;

    public function definition(): array
    {
        $hasArtist = fake()->boolean(80);
        $hasRelease = fake()->boolean(60);
        $hasTrack = fake()->boolean(40);
        $hasDistribution = fake()->boolean(50);

        $periodEnd = fake()->dateTimeBetween('-12 months', 'now');

        return [
            'source' => fake()->randomElement(RevenueEntry::SOURCES),
            'platform' => fake()->optional(0.5)->randomElement([
                'spotify', 'apple_music', 'youtube_music', 'boomplay',
            ]),
            'artist_id' => $hasArtist ? Artist::factory() : null,
            'release_id' => $hasRelease ? Release::factory() : null,
            'track_id' => $hasTrack ? Track::factory() : null,
            'distribution_id' => $hasDistribution ? Distribution::factory() : null,
            'amount' => fake()->randomFloat(2, 100, 500000),
            'currency' => fake()->randomElement(['NGN', 'USD', 'ZAR', 'GBP']),
            'period_start' => (clone $periodEnd)->modify('-30 days')->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'received_at' => fake()->optional(0.7)->dateTimeBetween($periodEnd, 'now')?->format('Y-m-d'),
            'reference' => fake()->optional(0.5)->bothify('REF-####-????'),
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => User::factory(),
        ];
    }
}