<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArtistSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@kayfactory.test')->first();
        $artistManager = User::where('email', 'artistmanager@kayfactory.test')->first();

        $artists = [
            // Afrobeats
            [
                'name' => 'Tunde Wave',
                'real_name' => 'Tunde Adebayo',
                'email' => 'tunde.wave@kayfactory.test',
                'phone' => '+2348010000001',
                'genre' => 'Afrobeats',
                'country' => 'Nigeria',
                'city' => 'Lagos',
                'status' => Artist::STATUS_SIGNED,
                'bio' => 'Afrobeats artist blending Lagos street energy with melodic hooks.',
                'social_links' => [
                    'spotify' => 'https://open.spotify.com/artist/tundewave',
                    'instagram' => 'https://instagram.com/tundewave',
                ],
            ],
            [
                'name' => 'Sade Ember',
                'real_name' => 'Sade Okonkwo',
                'email' => 'sade.ember@kayfactory.test',
                'phone' => '+2348010000002',
                'genre' => 'Afrobeats',
                'country' => 'Nigeria',
                'city' => 'Abuja',
                'status' => Artist::STATUS_SIGNED,
                'bio' => 'Vocalist and songwriter fusing Afrobeats with soulful R&B.',
                'social_links' => [
                    'spotify' => 'https://open.spotify.com/artist/sadeember',
                    'youtube' => 'https://youtube.com/@sadeember',
                ],
            ],
            [
                'name' => 'Kola Bright',
                'real_name' => 'Kola Ibrahim',
                'email' => 'kola.bright@kayfactory.test',
                'phone' => '+2348010000003',
                'genre' => 'Afrobeats',
                'country' => 'Nigeria',
                'city' => 'Port Harcourt',
                'status' => Artist::STATUS_IN_TALKS,
                'bio' => 'Emerging Afrobeats act with a dance-driven catalogue.',
                'social_links' => null,
            ],

            // Amapiano
            [
                'name' => 'Lerato Sesh',
                'real_name' => 'Lerato Mokoena',
                'email' => 'lerato.sesh@kayfactory.test',
                'phone' => '+27801000001',
                'genre' => 'Amapiano',
                'country' => 'South Africa',
                'city' => 'Johannesburg',
                'status' => Artist::STATUS_SIGNED,
                'bio' => 'Amapiano producer and vocalist with a signature log-drum groove.',
                'social_links' => [
                    'spotify' => 'https://open.spotify.com/artist/leratosess',
                    'soundcloud' => 'https://soundcloud.com/leratosess',
                ],
            ],
            [
                'name' => 'Thabo Keys',
                'real_name' => 'Thabo Nkosi',
                'email' => 'thabo.keys@kayfactory.test',
                'phone' => '+27801000002',
                'genre' => 'Amapiano',
                'country' => 'South Africa',
                'city' => 'Pretoria',
                'status' => Artist::STATUS_IN_TALKS,
                'bio' => 'Piano-led Amapiano artist with a soulful touch.',
                'social_links' => null,
            ],
            [
                'name' => 'Naledi Groove',
                'real_name' => 'Naledi Dlamini',
                'email' => 'naledi.groove@kayfactory.test',
                'phone' => '+27801000003',
                'genre' => 'Amapiano',
                'country' => 'South Africa',
                'city' => 'Durban',
                'status' => Artist::STATUS_INACTIVE,
                'bio' => 'Amapiano vocalist on a temporary break from recording.',
                'social_links' => [
                    'instagram' => 'https://instagram.com/naledigroove',
                ],
            ],

            // Hip Hop
            [
                'name' => 'MC Rell',
                'real_name' => 'Chukwuemeka Obi',
                'email' => 'mc.rell@kayfactory.test',
                'phone' => '+2348010000004',
                'genre' => 'Hip Hop',
                'country' => 'Nigeria',
                'city' => 'Enugu',
                'status' => Artist::STATUS_SIGNED,
                'bio' => 'Lyricist with a punchline-heavy delivery and conscious themes.',
                'social_links' => [
                    'spotify' => 'https://open.spotify.com/artist/mcrell',
                    'twitter' => 'https://twitter.com/mcrell',
                ],
            ],
            [
                'name' => 'Young Ade',
                'real_name' => 'Ade Olawale',
                'email' => 'young.ade@kayfactory.test',
                'phone' => '+2348010000005',
                'genre' => 'Hip Hop',
                'country' => 'Nigeria',
                'city' => 'Ibadan',
                'status' => Artist::STATUS_IN_TALKS,
                'bio' => 'Trap-influenced hip hop act from the Ibadan underground.',
                'social_links' => null,
            ],
            [
                'name' => 'Zara Flow',
                'real_name' => 'Zara Bello',
                'email' => 'zara.flow@kayfactory.test',
                'phone' => '+2348010000006',
                'genre' => 'Hip Hop',
                'country' => 'Nigeria',
                'city' => 'Kano',
                'status' => Artist::STATUS_FORMER,
                'bio' => 'Former label artist. Catalogue remains under Kay Factory.',
                'social_links' => [
                    'instagram' => 'https://instagram.com/zaraflow',
                ],
            ],

            // R&B
            [
                'name' => 'Amara Soul',
                'real_name' => 'Amara Nwosu',
                'email' => 'amara.soul@kayfactory.test',
                'phone' => '+2348010000007',
                'genre' => 'R&B',
                'country' => 'Nigeria',
                'city' => 'Lagos',
                'status' => Artist::STATUS_SIGNED,
                'bio' => 'R&B vocalist with a warm, textured tone.',
                'social_links' => [
                    'spotify' => 'https://open.spotify.com/artist/amarasoul',
                    'apple_music' => 'https://music.apple.com/artist/amarasoul',
                ],
            ],
            [
                'name' => 'Dami Velvet',
                'real_name' => 'Damilola Akin',
                'email' => 'dami.velvet@kayfactory.test',
                'phone' => '+2348010000008',
                'genre' => 'R&B',
                'country' => 'Nigeria',
                'city' => 'Lagos',
                'status' => Artist::STATUS_SIGNED,
                'bio' => 'Neo-soul and R&B artist writing intimate love songs.',
                'social_links' => [
                    'spotify' => 'https://open.spotify.com/artist/damivelvet',
                    'website' => 'https://damivelvet.example.com',
                ],
            ],
            [
                'name' => 'Ify Nocturne',
                'real_name' => 'Ifeoma Uche',
                'email' => 'ify.nocturne@kayfactory.test',
                'phone' => '+2348010000009',
                'genre' => 'R&B',
                'country' => 'Nigeria',
                'city' => 'Owerri',
                'status' => Artist::STATUS_IN_TALKS,
                'bio' => 'Late-night R&B with jazz-adjacent arrangements.',
                'social_links' => null,
            ],
        ];

        foreach ($artists as $data) {
            Artist::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, [
                    'manager_id' => $artistManager?->id,
                    'created_by' => $superAdmin?->id,
                ])
            );
        }
    }
}