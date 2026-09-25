<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-2 years', 'now');
        $end = (clone $start)->modify('+' . fake()->numberBetween(6, 36) . ' months');

        return [
            'artist_id' => Artist::factory(),
            'title' => fake()->sentence(4),
            'type' => fake()->randomElement(Contract::TYPES),
            'status' => fake()->randomElement(Contract::STATUSES),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'signed_date' => fake()->optional(0.7)->dateTimeBetween($start, 'now')?->format('Y-m-d'),
            'advance_amount' => fake()->optional(0.6)->randomFloat(2, 0, 5_000_000),
            'currency' => fake()->randomElement(['NGN', 'USD', 'ZAR', 'GBP']),
            'royalty_rate' => fake()->optional(0.7)->randomFloat(2, 0, 100),
            'terms' => fake()->optional(0.5)->paragraph(),
            'document_path' => null,
            'created_by' => User::factory(),
        ];
    }
}