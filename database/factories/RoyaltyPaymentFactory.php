<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\RoyaltyPayment;
use App\Models\RoyaltyStatement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoyaltyPayment>
 */
class RoyaltyPaymentFactory extends Factory
{
    protected $model = RoyaltyPayment::class;

    public function definition(): array
    {
        return [
            'statement_id' => RoyaltyStatement::factory(),
            'artist_id' => Artist::factory(),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'currency' => fake()->randomElement(['NGN', 'USD', 'ZAR', 'GBP']),
            'paid_at' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'method' => fake()->randomElement(RoyaltyPayment::METHODS),
            'reference' => fake()->optional(0.6)->bothify('PAY-####-????'),
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => User::factory(),
        ];
    }
}