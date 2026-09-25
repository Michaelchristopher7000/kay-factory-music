<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function about()
    {
        return view('public.about', [
            'meta' => [
                'title' => 'About — Kay Factory Music',
                'description' => 'Kay Factory Music is a record label built for the sound of now.',
                'canonical' => route('about'),
            ],
        ]);
    }

    public function services()
    {
        return view('public.services', [
            'meta' => [
                'title' => 'Services — Kay Factory Music',
                'description' => 'Artist development, production, distribution, and creative direction.',
                'canonical' => route('services'),
            ],
        ]);
    }

    public function contact()
    {
        return view('public.contact', [
            'meta' => [
                'title' => 'Contact — Kay Factory Music',
                'description' => 'Get in touch with Kay Factory Music.',
                'canonical' => route('contact'),
            ],
        ]);
    }

    public function submitDemo()
    {
        return view('public.submit-demo', [
            'meta' => [
                'title' => 'Submit Your Music — Kay Factory Music',
                'description' => 'Think you have what it takes? Submit your music to Kay Factory Music.',
                'canonical' => route('submit-demo'),
            ],
        ]);
    }
}