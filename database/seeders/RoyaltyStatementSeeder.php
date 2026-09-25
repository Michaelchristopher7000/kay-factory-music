<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Contract;
use App\Models\RevenueEntry;
use App\Models\RoyaltyStatement;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoyaltyStatementSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@kayfactory.test')->first();
        $labelManager = User::where('email', 'manager@kayfactory.test')->first();

        $creators = array_values(array_filter([$superAdmin, $labelManager]));

        if (empty($creators)) {
            return;
        }

        $artistsWithRevenue = RevenueEntry::query()
            ->whereNotNull('artist_id')
            ->distinct()
            ->pluck('artist_id');

        if ($artistsWithRevenue->isEmpty()) {
            return;
        }

        $artists = Artist::whereIn('id', $artistsWithRevenue)->get();

        foreach ($artists as $artist) {
            // Find a currency this artist actually earned in.
            $currency = RevenueEntry::query()
                ->where('artist_id', $artist->id)
                ->value('currency') ?? 'NGN';

            // Latest active contract rate, else 50% fallback for demo.
            $contract = Contract::query()
                ->where('artist_id', $artist->id)
                ->where('status', Contract::STATUS_ACTIVE)
                ->orderByDesc('signed_date')
                ->orderByDesc('id')
                ->first();

            $rate = $contract?->royalty_rate !== null
                ? (float) $contract->royalty_rate
                : 50.0;

            // One statement covering last 6 months.
            $periodStart = now()->subMonths(6)->startOfMonth();
            $periodEnd = now()->endOfMonth();

            $existing = RoyaltyStatement::query()
                ->where('artist_id', $artist->id)
                ->whereDate('period_start', $periodStart->toDateString())
                ->whereDate('period_end', $periodEnd->toDateString())
                ->where('currency', $currency)
                ->first();

            if ($existing) {
                continue;
            }

            $statement = RoyaltyStatement::create([
                'artist_id' => $artist->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'status' => RoyaltyStatement::STATUS_DRAFT,
                'currency' => $currency,
                'royalty_rate' => $rate,
                'notes' => 'Seeded statement for ' . $artist->name,
                'created_by' => fake()->randomElement($creators)->id,
            ]);

            $statement->generateLines($contract?->id);
        }
    }
}