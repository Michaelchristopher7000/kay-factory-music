<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\Release;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Release>
 */
class ReleaseFactory extends Factory
{
    protected $model = Release::class;

    public function definition(): array
    {
        $release = fake()->optional(0.7)->dateTimeBetween('-2 years', '+6 months');

        return [
            'artist_id' => Artist::factory(),
            'title' => fake()->unique()->sentence(3),
            'type' => fake()->randomElement(Release::TYPES),
            'status' => fake()->randomElement(Release::STATUSES),
            'release_date' => $release?->format('Y-m-d'),
            'pre_save_date' => $release
                ? (clone $release)->modify('-21 days')->format('Y-m-d')
                : null,
            'upc' => null,
            'description' => fake()->optional(0.5)->paragraph(),
            'cover_art_path' => null,
            'label_copy' => fake()->optional(0.6)->randomElement([
                '℗ 2026 Kay Factory Music',
                '℗ 2025 Kay Factory Music',
                '© 2026 Kay Factory Music. All rights reserved.',
            ]),
            'created_by' => User::factory(),
        ];
    }
}