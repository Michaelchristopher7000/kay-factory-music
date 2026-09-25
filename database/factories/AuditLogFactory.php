<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_email' => fake()->safeEmail(),
            'user_name' => fake()->name(),
            'action' => fake()->randomElement(AuditLog::ACTIONS),
            'model_type' => fake()->randomElement([
                'Artist', 'Contract', 'Track', 'Release', 'Distribution',
                'RevenueEntry', 'Expense', 'RoyaltyStatement', 'RoyaltyPayment',
            ]),
            'model_id' => fake()->numberBetween(1, 100),
            'model_label' => 'KFM-TEST-' . fake()->numberBetween(1, 9999),
            'changes' => ['attributes' => ['name' => fake()->word()]],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}