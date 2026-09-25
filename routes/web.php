<?php

use App\Http\Controllers\Web\ArtistWebController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LegalController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\ReleaseWebController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------
// PUBLIC WEBSITE — Laravel owns the URLs, React renders the UI
// ---------------------------------------------------------------
Route::get('/',                [HomeController::class, 'index'])->name('home');
Route::get('/artists',         [ArtistWebController::class, 'index'])->name('artists.index');
Route::get('/artists/{slug}',  [ArtistWebController::class, 'show'])->name('artists.show');
Route::get('/music',           [ReleaseWebController::class, 'index'])->name('music.index');
Route::get('/music/{slug}',    [ReleaseWebController::class, 'show'])->name('music.show');
Route::get('/about',           [PageController::class, 'about'])->name('about');
Route::get('/services',        [PageController::class, 'services'])->name('services');
Route::get('/contact',         [PageController::class, 'contact'])->name('contact');
Route::get('/submit-demo',     [PageController::class, 'submitDemo'])->name('submit-demo');

// ---------------------------------------------------------------
// LEGAL PAGES
// ---------------------------------------------------------------
Route::get('/privacy-policy',  [LegalController::class, 'privacy'])->name('privacy-policy');
Route::get('/terms',           [LegalController::class, 'terms'])->name('terms');
Route::get('/cookie-policy',   [LegalController::class, 'cookies'])->name('cookie-policy');

// ---------------------------------------------------------------
// STAFF DASHBOARD — single entry, React Router owns internal nav
// ---------------------------------------------------------------
Route::get('/staff/{any?}', function () {
    return view('staff');
})->where('any', '.*')->name('staff');