<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Artist;

class ArtistWebController extends Controller
{
    public function index()
    {
        return view('public.artists.index', [
            'meta' => [
                'title' => 'Artists — Kay Factory Music',
                'description' => 'Meet the artists shaping the sound of Kay Factory Music.',
                'canonical' => route('artists.index'),
            ],
        ]);
    }

    public function show(string $slug)
    {
        $artist = Artist::where('slug', $slug)
            ->where('status', Artist::STATUS_SIGNED)
            ->firstOrFail();

        return view('public.artists.show', [
            'artistSlug' => $slug,
            'meta' => [
                'title' => "{$artist->name} — Kay Factory Music",
                'description' => $artist->bio ?: "Listen to {$artist->name} on Kay Factory Music.",
                'canonical' => route('artists.show', $slug),
                'og_image' => $artist->avatar,
            ],
        ]);
    }
}