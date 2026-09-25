<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Release;

class ReleaseWebController extends Controller
{
    public function index()
    {
        return view('public.music.index', [
            'meta' => [
                'title' => 'Music — Kay Factory Music',
                'description' => 'Explore the Kay Factory Music catalogue — singles, EPs, albums and more.',
                'canonical' => route('music.index'),
            ],
        ]);
    }

    public function show(string $slug)
    {
        $release = Release::where('slug', $slug)
            ->where('status', Release::STATUS_RELEASED)
            ->firstOrFail();

        return view('public.music.show', [
            'releaseSlug' => $slug,
            'meta' => [
                'title' => "{$release->title} — Kay Factory Music",
                'description' => $release->description
                    ?: "Listen to {$release->title} on Kay Factory Music.",
                'canonical' => route('music.show', $slug),
                'og_image' => $release->cover_art_path,
            ],
        ]);
    }
}