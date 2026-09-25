<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class LegalController extends Controller
{
    public function privacy()
    {
        return view('public.privacy-policy', [
            'meta' => [
                'title' => 'Privacy Policy — Kay Factory Music',
                'description' => 'How Kay Factory Music collects, uses, and protects your information.',
                'canonical' => route('privacy-policy'),
            ],
        ]);
    }

    public function terms()
    {
        return view('public.terms', [
            'meta' => [
                'title' => 'Terms & Conditions — Kay Factory Music',
                'description' => 'The terms governing use of the Kay Factory Music website and platform.',
                'canonical' => route('terms'),
            ],
        ]);
    }

    public function cookies()
    {
        return view('public.cookie-policy', [
            'meta' => [
                'title' => 'Cookie Policy — Kay Factory Music',
                'description' => 'How Kay Factory Music uses cookies on this website.',
                'canonical' => route('cookie-policy'),
            ],
        ]);
    }
}