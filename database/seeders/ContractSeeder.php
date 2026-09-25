<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@kayfactory.test')->first();
        $labelManager = User::where('email', 'manager@kayfactory.test')->first();

        $artists = Artist::all();

        if ($artists->isEmpty()) {
            return;
        }

        $creators = array_values(array_filter([$superAdmin, $labelManager]));

        if (empty($creators)) {
            return;
        }

        foreach ($artists as $artist) {
            // Signed and in_talks artists get contracts; inactive/former still may.
            // Give each artist 1-2 contracts with varied statuses.
            $count = fake()->numberBetween(1, 2);

            for ($i = 0; $i < $count; $i++) {
                $status = fake()->randomElement(Contract::STATUSES);
                $type = fake()->randomElement(Contract::TYPES);
                $start = fake()->dateTimeBetween('-2 years', 'now');
                $end = (clone $start)->modify('+' . fake()->numberBetween(12, 36) . ' months');

                Contract::updateOrCreate(
                    [
                        'artist_id' => $artist->id,
                        'type' => $type,
                        'title' => $this->titleFor($type, $artist->name),
                    ],
                    [
                        'status' => $status,
                        'start_date' => $start->format('Y-m-d'),
                        'end_date' => $end->format('Y-m-d'),
                        'signed_date' => in_array($status, [
                            Contract::STATUS_ACTIVE,
                            Contract::STATUS_EXPIRED,
                            Contract::STATUS_TERMINATED,
                        ], true)
                            ? (clone $start)->format('Y-m-d')
                            : null,
                        'advance_amount' => fake()->optional(0.6)->randomFloat(2, 100_000, 5_000_000),
                        'currency' => 'NGN',
                        'royalty_rate' => fake()->optional(0.75)->randomFloat(2, 30, 90),
                        'terms' => fake()->optional(0.5)->paragraph(),
                        'document_path' => null,
                        'created_by' => fake()->randomElement($creators)->id,
                    ]
                );
            }
        }
    }

    private function titleFor(string $type, string $artistName): string
    {
        $label = match ($type) {
            Contract::TYPE_ARTIST_AGREEMENT => 'Artist Agreement',
            Contract::TYPE_RECORDING_AGREEMENT => 'Recording Agreement',
            Contract::TYPE_DISTRIBUTION_AGREEMENT => 'Distribution Agreement',
            Contract::TYPE_MANAGEMENT_AGREEMENT => 'Management Agreement',
            Contract::TYPE_PUBLISHING_AGREEMENT => 'Publishing Agreement',
            Contract::TYPE_LICENSING_AGREEMENT => 'Licensing Agreement',
            default => 'Agreement',
        };

        return "{$label} — {$artistName}";
    }
}