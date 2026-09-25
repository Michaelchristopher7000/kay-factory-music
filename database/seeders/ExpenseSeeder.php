<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Expense;
use App\Models\Release;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@kayfactory.test')->first();
        $labelManager = User::where('email', 'manager@kayfactory.test')->first();

        $creators = array_values(array_filter([$superAdmin, $labelManager]));

        if (empty($creators)) {
            return;
        }

        $artists = Artist::all();
        $releases = Release::all();

        if ($artists->isEmpty()) {
            return;
        }

        // Studio + marketing expenses per signed artist.
        foreach ($artists as $artist) {
            if ($artist->status !== 'signed') {
                continue;
            }

            // Studio time
            Expense::updateOrCreate(
                [
                    'category' => 'studio',
                    'artist_id' => $artist->id,
                    'reference' => 'STU-' . str_pad((string) $artist->id, 4, '0', STR_PAD_LEFT),
                ],
                [
                    'release_id' => null,
                    'track_id' => null,
                    'distribution_id' => null,
                    'amount' => fake()->randomFloat(2, 50000, 800000),
                    'currency' => 'NGN',
                    'incurred_at' => now()->subMonths(fake()->numberBetween(1, 10))->format('Y-m-d'),
                    'paid_at' => now()->subMonths(fake()->numberBetween(0, 8))->format('Y-m-d'),
                    'description' => 'Studio recording session',
                    'notes' => null,
                    'created_by' => fake()->randomElement($creators)->id,
                ]
            );

            // Marketing
            Expense::updateOrCreate(
                [
                    'category' => 'marketing',
                    'artist_id' => $artist->id,
                    'reference' => 'MKT-' . str_pad((string) $artist->id, 4, '0', STR_PAD_LEFT),
                ],
                [
                    'release_id' => null,
                    'track_id' => null,
                    'distribution_id' => null,
                    'amount' => fake()->randomFloat(2, 100000, 1500000),
                    'currency' => 'NGN',
                    'incurred_at' => now()->subMonths(fake()->numberBetween(1, 10))->format('Y-m-d'),
                    'paid_at' => now()->subMonths(fake()->numberBetween(0, 8))->format('Y-m-d'),
                    'description' => 'Marketing and promotion',
                    'notes' => null,
                    'created_by' => fake()->randomElement($creators)->id,
                ]
            );
        }

        // Video costs per released release.
        foreach ($releases as $release) {
            if ($release->status !== 'released') {
                continue;
            }

            Expense::updateOrCreate(
                [
                    'category' => 'video',
                    'release_id' => $release->id,
                    'reference' => 'VID-' . $release->release_code,
                ],
                [
                    'artist_id' => $release->artist_id,
                    'track_id' => null,
                    'distribution_id' => null,
                    'amount' => fake()->randomFloat(2, 200000, 2000000),
                    'currency' => 'NGN',
                    'incurred_at' => $release->release_date?->format('Y-m-d'),
                    'paid_at' => $release->release_date?->copy()->addDays(30)->format('Y-m-d'),
                    'description' => 'Music video production',
                    'notes' => null,
                    'created_by' => fake()->randomElement($creators)->id,
                ]
            );
        }
    }
}