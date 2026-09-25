<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\Seeder;

class TrackSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@kayfactory.test')->first();
        $labelManager = User::where('email', 'manager@kayfactory.test')->first();
        $artistManager = User::where('email', 'artistmanager@kayfactory.test')->first();

        $artists = Artist::all();

        if ($artists->isEmpty()) {
            return;
        }

        $creators = array_values(array_filter([$superAdmin, $labelManager, $artistManager]));

        if (empty($creators)) {
            return;
        }

        // Hand-crafted seed data per artist, so titles are realistic and not random.
        $library = [
            'Tunde Wave' => [
                ['title' => 'Lagos Nights', 'genre' => 'Afrobeats', 'bpm' => 102, 'key' => 'F#m', 'duration_seconds' => 195],
                ['title' => 'Danfo Groove', 'genre' => 'Afrobeats', 'bpm' => 108, 'key' => 'Am', 'duration_seconds' => 210],
                ['title' => 'Sunrise in Surulere', 'genre' => 'Afrobeats', 'bpm' => 98, 'key' => 'G', 'duration_seconds' => 224],
            ],
            'Sade Ember' => [
                ['title' => 'Ember', 'genre' => 'Afrobeats', 'bpm' => 96, 'key' => 'C', 'duration_seconds' => 218],
                ['title' => 'Softly Yours', 'genre' => 'R&B', 'bpm' => 82, 'key' => 'Dm', 'duration_seconds' => 240],
            ],
            'Kola Bright' => [
                ['title' => 'Bright Side', 'genre' => 'Afrobeats', 'bpm' => 104, 'key' => 'E', 'duration_seconds' => 188],
            ],
            'Lerato Sesh' => [
                ['title' => 'Log Drum Prayer', 'genre' => 'Amapiano', 'bpm' => 112, 'key' => 'A', 'duration_seconds' => 320],
                ['title' => 'Jozi Sunset', 'genre' => 'Amapiano', 'bpm' => 110, 'key' => 'Em', 'duration_seconds' => 296],
            ],
            'Thabo Keys' => [
                ['title' => 'Piano Waves', 'genre' => 'Amapiano', 'bpm' => 114, 'key' => 'Bm', 'duration_seconds' => 288],
                ['title' => 'Midnight Keys', 'genre' => 'Amapiano', 'bpm' => 108, 'key' => 'Gm', 'duration_seconds' => 312],
            ],
            'Naledi Groove' => [
                ['title' => 'Slow Dance', 'genre' => 'Amapiano', 'bpm' => 106, 'key' => 'F', 'duration_seconds' => 275],
            ],
            'MC Rell' => [
                ['title' => 'Street Sermon', 'genre' => 'Hip Hop', 'bpm' => 88, 'key' => 'Am', 'duration_seconds' => 205, 'is_explicit' => true],
                ['title' => 'Enugu Anthem', 'genre' => 'Hip Hop', 'bpm' => 92, 'key' => 'Dm', 'duration_seconds' => 198, 'is_explicit' => true],
            ],
            'Young Ade' => [
                ['title' => 'Ibadan Trap', 'genre' => 'Hip Hop', 'bpm' => 140, 'key' => 'C', 'duration_seconds' => 176, 'is_explicit' => true],
            ],
            'Zara Flow' => [
                ['title' => 'Flow State', 'genre' => 'Hip Hop', 'bpm' => 90, 'key' => 'G', 'duration_seconds' => 212],
            ],
            'Amara Soul' => [
                ['title' => 'Velvet Hour', 'genre' => 'R&B', 'bpm' => 78, 'key' => 'C#m', 'duration_seconds' => 252],
                ['title' => 'Warmth', 'genre' => 'R&B', 'bpm' => 74, 'key' => 'F', 'duration_seconds' => 268],
            ],
            'Dami Velvet' => [
                ['title' => 'Slow Burn', 'genre' => 'R&B', 'bpm' => 80, 'key' => 'E', 'duration_seconds' => 244],
                ['title' => 'Midnight Letter', 'genre' => 'R&B', 'bpm' => 76, 'key' => 'Am', 'duration_seconds' => 260],
            ],
            'Ify Nocturne' => [
                ['title' => 'Nocturne I', 'genre' => 'R&B', 'bpm' => 72, 'key' => 'D', 'duration_seconds' => 286],
            ],
        ];

        foreach ($artists as $artist) {
            $tracks = $library[$artist->name] ?? [];

            foreach ($tracks as $trackData) {
                Track::updateOrCreate(
                    [
                        'artist_id' => $artist->id,
                        'title' => $trackData['title'],
                    ],
                    array_merge([
                        'genre' => $artist->genre,
                        'language' => 'en',
                        'bpm' => null,
                        'key' => null,
                        'is_explicit' => false,
                        'composer' => null,
                        'writers' => null,
                        'producers' => null,
                        'featured_artists' => null,
                        'recorded_date' => null,
                        'lyrics' => null,
                        'audio_path' => null,
                        'notes' => null,
                        'duration_seconds' => null,
                        'isrc' => null,
                        'created_by' => fake()->randomElement($creators)->id,
                    ], $trackData)
                );
            }
        }
    }
}