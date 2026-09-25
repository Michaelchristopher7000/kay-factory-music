<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Artist>
 */
class ArtistFactory extends Factory
{
    protected $model = Artist::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->name(),
            'real_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'bio' => fake()->paragraph(),
            'avatar' => null,
            'genre' => fake()->randomElement(['Afrobeats', 'Amapiano', 'Hip Hop', 'R&B']),
            'country' => fake()->country(),
            'city' => fake()->city(),
            'status' => fake()->randomElement(Artist::STATUSES),
            'manager_id' => null,
            'created_by' => User::factory(),
            'social_links' => null,
        ];
    }
}