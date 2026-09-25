<?php

namespace Database\Seeders;

use App\Models\Distribution;
use App\Models\Release;
use App\Models\RevenueEntry;
use App\Models\User;
use Illuminate\Database\Seeder;

class RevenueEntrySeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@kayfactory.test')->first();
        $labelManager = User::where('email', 'manager@kayfactory.test')->first();

        $creators = array_values(array_filter([$superAdmin, $labelManager]));

        if (empty($creators)) {
            return;
        }

        // For each live distribution, seed a few months of streaming revenue.
        $liveDistributions = Distribution::query()
            ->where('status', 'live')
            ->with('release')
            ->get();

        if ($liveDistributions->isEmpty()) {
            return;
        }

        foreach ($liveDistributions as $distribution) {
            $release = $distribution->release;

            if (! $release) {
                continue;
            }

            // 3 months of revenue per distribution.
            for ($i = 0; $i < 3; $i++) {
                $periodEnd = now()->subMonths($i);
                $periodStart = (clone $periodEnd)->subDays(30);

                RevenueEntry::updateOrCreate(
                    [
                        'distribution_id' => $distribution->id,
                        'period_end' => $periodEnd->format('Y-m-d'),
                        'source' => 'streaming',
                    ],
                    [
                        'platform' => $distribution->platform,
                        'artist_id' => $release->artist_id,
                        'release_id' => $release->id,
                        'track_id' => null,
                        'amount' => fake()->randomFloat(2, 500, 250000),
                        'currency' => 'NGN',
                        'period_start' => $periodStart->format('Y-m-d'),
                        'received_at' => (clone $periodEnd)->addDays(45)->format('Y-m-d'),
                        'reference' => 'STM-' . strtoupper($distribution->platform) . '-' . $periodEnd->format('Ym'),
                        'notes' => null,
                        'created_by' => fake()->randomElement($creators)->id,
                    ]
                );
            }
        }
    }
}