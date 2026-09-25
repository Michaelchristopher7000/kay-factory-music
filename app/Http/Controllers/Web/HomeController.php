<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('public.home', [
            'meta' => [
                'title' => 'Kay Factory Music — Where Sound Becomes Legacy',
                'description' => 'A home for bold artists, unforgettable records, and sounds built to last.',
                'canonical' => route('home'),
            ],
        ]);
    }
}