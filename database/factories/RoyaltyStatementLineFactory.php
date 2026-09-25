<?php

namespace Database\Factories;

use App\Models\RevenueEntry;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoyaltyStatementLine>
 */
class RoyaltyStatementLineFactory extends Factory
{
    protected $model = RoyaltyStatementLine::class;

    public function definition(): array
    {
        $revenue = fake()->randomFloat(2, 100, 100000);
        $rate = fake()->randomFloat(2, 10, 90);

        return [
            'statement_id' => RoyaltyStatement::factory(),
            'revenue_entry_id' => null,
            'contract_id' => null,
            'release_id' => null,
            'track_id' => null,
            'source' => fake()->randomElement(['streaming', 'sync', 'publishing']),
            'description' => null,
            'revenue_amount' => $revenue,
            'royalty_rate' => $rate,
            'royalty_amount' => round($revenue * $rate / 100, 2),
        ];
    }
}