<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\Distribution;
use App\Models\Expense;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        $hasArtist = fake()->boolean(70);
        $hasRelease = fake()->boolean(50);
        $hasTrack = fake()->boolean(30);
        $hasDistribution = fake()->boolean(30);

        $incurred = fake()->dateTimeBetween('-12 months', 'now');

        return [
            'category' => fake()->randomElement(Expense::CATEGORIES),
            'artist_id' => $hasArtist ? Artist::factory() : null,
            'release_id' => $hasRelease ? Release::factory() : null,
            'track_id' => $hasTrack ? Track::factory() : null,
            'distribution_id' => $hasDistribution ? Distribution::factory() : null,
            'amount' => fake()->randomFloat(2, 100, 200000),
            'currency' => fake()->randomElement(['NGN', 'USD', 'ZAR', 'GBP']),
            'incurred_at' => $incurred->format('Y-m-d'),
            'paid_at' => fake()->optional(0.7)->dateTimeBetween($incurred, 'now')?->format('Y-m-d'),
            'reference' => fake()->optional(0.6)->bothify('INV-####-????'),
            'description' => fake()->sentence(),
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => User::factory(),
        ];
    }
}