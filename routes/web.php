<?php

use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Site;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

// Public contact form: 5 messages per hour per visitor.
RateLimiter::for('contact', fn (Request $request) => Limit::perHour(5)->by((string) $request->ip()));

Route::get('/', Site\HomeController::class)->name('home');

Route::get('dashboard', DashboardRedirectController::class)->middleware('auth')->name('dashboard');

require __DIR__.'/staff.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

Route::get('about', Site\AboutController::class)->name('site.about');
Route::get('contact', [Site\ContactController::class, 'show'])->name('site.contact');
Route::post('contact', [Site\ContactController::class, 'store'])->middleware('throttle:contact')->name('site.contact.store');

// Public reading routes. Keep these LAST: they are catch-alls for /{section} and /{section}/{story}.
Route::get('search', Site\SearchController::class)->name('site.search');
Route::get('{categorySlug}', Site\CategoryController::class)
    ->where('categorySlug', '[a-z0-9-]+')->name('site.category');
Route::get('{categorySlug}/{articleSlug}', Site\ArticleController::class)
    ->where(['categorySlug' => '[a-z0-9-]+', 'articleSlug' => '[a-z0-9-]+'])->name('site.article');
