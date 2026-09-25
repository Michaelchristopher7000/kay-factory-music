<?php

namespace Database\Factories;

use App\Models\Distribution;
use App\Models\Release;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Distribution>
 */
class DistributionFactory extends Factory
{
    protected $model = Distribution::class;

    public function definition(): array
    {
        $status = fake()->randomElement(Distribution::STATUSES);

        return [
            'release_id' => Release::factory(),
            'platform' => fake()->randomElement(Distribution::PLATFORMS),
            'distributor' => fake()->optional(0.7)->randomElement([
                'Empire', 'The Orchard', 'Believe', 'TuneCore', 'CD Baby',
            ]),
            'status' => $status,
            'territory' => fake()->optional(0.7)->randomElement([
                'worldwide', 'africa', 'eu', 'us', 'uk', 'ng',
            ]),
            'scheduled_for' => fake()->optional(0.5)->dateTimeBetween('-6 months', '+3 months')?->format('Y-m-d'),
            'submitted_at' => in_array($status, ['submitted', 'live', 'takedown'], true)
                ? fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d')
                : null,
            'live_at' => $status === 'live'
                ? fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d')
                : null,
            'takedown_at' => $status === 'takedown'
                ? fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d')
                : null,
            'platform_release_id' => fake()->optional(0.6)->bothify('PLT-########'),
            'platform_url' => fake()->optional(0.5)->url(),
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => User::factory(),
        ];
    }
}