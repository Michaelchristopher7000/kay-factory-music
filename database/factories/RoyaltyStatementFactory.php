<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\RoyaltyStatement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoyaltyStatement>
 */
class RoyaltyStatementFactory extends Factory
{
    protected $model = RoyaltyStatement::class;

    public function definition(): array
    {
        $periodEnd = fake()->dateTimeBetween('-12 months', 'now');
        $periodStart = (clone $periodEnd)->modify('-30 days');

        return [
            'artist_id' => Artist::factory(),
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'status' => RoyaltyStatement::STATUS_DRAFT,
            'currency' => fake()->randomElement(['NGN', 'USD', 'ZAR', 'GBP']),
            'royalty_rate' => fake()->randomFloat(2, 10, 90),
            'total_revenue' => 0,
            'total_royalty' => 0,
            'total_paid' => 0,
            'balance' => 0,
            'issued_at' => null,
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}