<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Track>
 */
class TrackFactory extends Factory
{
    protected $model = Track::class;

    public function definition(): array
    {
        $genres = ['Afrobeats', 'Amapiano', 'Hip Hop', 'R&B', 'Dancehall', 'Pop'];
        $languages = ['en', 'yo', 'ig', 'zu', 'pcm'];
        $keys = ['C', 'C#m', 'D', 'Dm', 'E', 'Em', 'F', 'F#m', 'G', 'Gm', 'A', 'Am', 'B', 'Bm'];

        return [
            'artist_id' => Artist::factory(),
            'title' => fake()->unique()->sentence(3),
            'isrc' => null, // set explicitly only in tests that need uniqueness
            'duration_seconds' => fake()->numberBetween(120, 360),
            'genre' => fake()->randomElement($genres),
            'language' => fake()->randomElement($languages),
            'bpm' => fake()->numberBetween(70, 180),
            'key' => fake()->randomElement($keys),
            'is_explicit' => fake()->boolean(30),
            'composer' => fake()->optional(0.5)->name(),
            'writers' => fake()->optional(0.7)->randomElements(
                [fake()->name(), fake()->name(), fake()->name()],
                fake()->numberBetween(1, 3)
            ),
            'producers' => fake()->optional(0.6)->randomElements(
                [fake()->name(), fake()->name()],
                fake()->numberBetween(1, 2)
            ),
            'featured_artists' => fake()->optional(0.3)->randomElements(
                [fake()->name(), fake()->name()],
                fake()->numberBetween(1, 2)
            ),
            'recorded_date' => fake()->optional(0.5)->dateTimeBetween('-3 years', 'now')?->format('Y-m-d'),
            'lyrics' => fake()->optional(0.2)->paragraph(),
            'audio_path' => null,
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => User::factory(),
        ];
    }
}