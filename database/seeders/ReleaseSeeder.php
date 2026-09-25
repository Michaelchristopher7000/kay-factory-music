<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReleaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@kayfactory.test')->first();
        $labelManager = User::where('email', 'manager@kayfactory.test')->first();
        $artistManager = User::where('email', 'artistmanager@kayfactory.test')->first();

        $creators = array_values(array_filter([$superAdmin, $labelManager, $artistManager]));

        if (empty($creators)) {
            return;
        }

        $artists = Artist::all();

        if ($artists->isEmpty()) {
            return;
        }

        // Hand-crafted releases per artist, referencing real track titles from TrackSeeder.
        $library = [
            'Tunde Wave' => [
                [
                    'title' => 'Lagos Nights',
                    'type' => Release::TYPE_SINGLE,
                    'status' => Release::STATUS_RELEASED,
                    'release_date' => '2025-06-14',
                    'tracks' => ['Lagos Nights'],
                ],
                [
                    'title' => 'Danfo Diaries',
                    'type' => Release::TYPE_EP,
                    'status' => Release::STATUS_RELEASED,
                    'release_date' => '2026-01-20',
                    'tracks' => ['Danfo Groove', 'Sunrise in Surulere'],
                ],
            ],
            'Sade Ember' => [
                [
                    'title' => 'Ember',
                    'type' => Release::TYPE_SINGLE,
                    'status' => Release::STATUS_RELEASED,
                    'release_date' => '2025-11-02',
                    'tracks' => ['Ember'],
                ],
                [
                    'title' => 'Softly Yours',
                    'type' => Release::TYPE_SINGLE,
                    'status' => Release::STATUS_SCHEDULED,
                    'release_date' => '2026-11-10',
                    'tracks' => ['Softly Yours'],
                ],
            ],
            'Kola Bright' => [
                [
                    'title' => 'Bright Side',
                    'type' => Release::TYPE_SINGLE,
                    'status' => Release::STATUS_DRAFT,
                    'release_date' => null,
                    'tracks' => ['Bright Side'],
                ],
            ],
            'Lerato Sesh' => [
                [
                    'title' => 'Log Drum Prayer',
                    'type' => Release::TYPE_SINGLE,
                    'status' => Release::STATUS_RELEASED,
                    'release_date' => '2025-09-05',
                    'tracks' => ['Log Drum Prayer'],
                ],
                [
                    'title' => 'Jozi Sunsets',
                    'type' => Release::TYPE_EP,
                    'status' => Release::STATUS_SCHEDULED,
                    'release_date' => '2026-12-01',
                    'tracks' => ['Jozi Sunset'],
                ],
            ],
            'Thabo Keys' => [
                [
                    'title' => 'Piano Waves',
                    'type' => Release::TYPE_ALBUM,
                    'status' => Release::STATUS_RELEASED,
                    'release_date' => '2026-03-15',
                    'tracks' => ['Piano Waves', 'Midnight Keys'],
                ],
            ],
            'Naledi Groove' => [
                [
                    'title' => 'Slow Dance',
                    'type' => Release::TYPE_SINGLE,
                    'status' => Release::STATUS_ARCHIVED,
                    'release_date' => '2024-08-20',
                    'tracks' => ['Slow Dance'],
                ],
            ],
            'MC Rell' => [
                [
                    'title' => 'Street Sermon',
                    'type' => Release::TYPE_SINGLE,
                    'status' => Release::STATUS_RELEASED,
                    'release_date' => '2025-04-12',
                    'tracks' => ['Street Sermon'],
                ],
                [
                    'title' => 'Enugu Chronicles',
                    'type' => Release::TYPE_MIXTAPE,
                    'status' => Release::STATUS_RELEASED,
                    'release_date' => '2026-05-30',
                    'tracks' => ['Enugu Anthem'],
                ],
            ],
            'Young Ade' => [
                [
                    'title' => 'Ibadan Trap',
                    'type' => Release::TYPE_SINGLE,
                    'status' => Release::STATUS_SCHEDULED,
                    'release_date' => '2026-10-15',
                    'tracks' => ['Ibadan Trap'],
                ],
            ],
            'Zara Flow' => [
                [
                    'title' => 'Flow State',
                    'type' => Release::TYPE_SINGLE,
                    'status' => Release::STATUS_ARCHIVED,
                    'release_date' => '2024-02-10',
                    'tracks' => ['Flow State'],
                ],
            ],
            'Amara Soul' => [
                [
                    'title' => 'Velvet Hour',
                    'type' => Release::TYPE_EP,
                    'status' => Release::STATUS_RELEASED,
                    'release_date' => '2026-02-14',
                    'tracks' => ['Velvet Hour', 'Warmth'],
                ],
            ],
            'Dami Velvet' => [
                [
                    'title' => 'Slow Burn',
                    'type' => Release::TYPE_SINGLE,
                    'status' => Release::STATUS_RELEASED,
                    'release_date' => '2025-12-01',
                    'tracks' => ['Slow Burn'],
                ],
                [
                    'title' => 'Midnight Letters',
                    'type' => Release::TYPE_EP,
                    'status' => Release::STATUS_SCHEDULED,
                    'release_date' => '2026-11-25',
                    'tracks' => ['Midnight Letter'],
                ],
            ],
            'Ify Nocturne' => [
                [
                    'title' => 'Nocturne I',
                    'type' => Release::TYPE_SINGLE,
                    'status' => Release::STATUS_DRAFT,
                    'release_date' => null,
                    'tracks' => ['Nocturne I'],
                ],
            ],
        ];

        foreach ($artists as $artist) {
            $releases = $library[$artist->name] ?? [];

            foreach ($releases as $releaseData) {
                $trackTitles = $releaseData['tracks'] ?? [];
                unset($releaseData['tracks']);

                $release = Release::updateOrCreate(
                    [
                        'artist_id' => $artist->id,
                        'title' => $releaseData['title'],
                    ],
                    array_merge([
                        'type' => Release::TYPE_SINGLE,
                        'status' => Release::STATUS_DRAFT,
                        'release_date' => null,
                        'pre_save_date' => null,
                        'upc' => null,
                        'description' => null,
                        'cover_art_path' => null,
                        'label_copy' => '℗ 2026 Kay Factory Music',
                        'created_by' => fake()->randomElement($creators)->id,
                    ], $releaseData)
                );

                // Attach tracks whose primary artist matches the release artist.
                $tracks = Track::query()
                    ->where('artist_id', $artist->id)
                    ->whereIn('title', $trackTitles)
                    ->get()
                    ->keyBy('title');

                $syncData = [];
                $position = 1;

                foreach ($trackTitles as $title) {
                    if ($tracks->has($title)) {
                        $syncData[$tracks->get($title)->id] = ['position' => $position++];
                    }
                }

                $release->tracks()->sync($syncData);
            }
        }
    }
}