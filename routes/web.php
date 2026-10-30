<?php

use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\MediaFileController;
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

// Image files: served from private storage; access decided per photo (public approval or staff permission).
Route::get('media/{media}/{variant}', MediaFileController::class)->whereNumber('media')->name('media.file');

Route::get('galleries', [Site\GalleryController::class, 'index'])->name('site.galleries');
Route::get('galleries/{slug}', [Site\GalleryController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('site.gallery');
Route::get('issues', [Site\IssueController::class, 'index'])->name('site.issues');
Route::get('issues/{slug}', [Site\IssueController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('site.issue');
Route::get('issues/{slug}/download', [Site\IssueController::class, 'download'])->where('slug', '[a-z0-9-]+')->name('site.issue.download');
Route::get('archives', [Site\ArchiveController::class, 'index'])->name('site.archives');
Route::get('archives/{year}', [Site\ArchiveController::class, 'year'])->whereNumber('year')->name('site.archive');

Route::post('assistant', [Site\AssistantController::class, 'ask'])->middleware('throttle:30,1')->name('site.assistant');
Route::post('assistant/escalate', [Site\AssistantController::class, 'escalate'])->middleware('throttle:contact')->name('site.assistant.escalate');

Route::get('events', Site\EventController::class)->name('site.events');

Route::get('sitemap.xml', Site\SitemapController::class)->name('site.sitemap');
Route::get('robots.txt', Site\RobotsController::class)->name('site.robots');

Route::get('about', Site\AboutController::class)->name('site.about');
Route::get('contact', [Site\ContactController::class, 'show'])->name('site.contact');
Route::post('contact', [Site\ContactController::class, 'store'])->middleware('throttle:contact')->name('site.contact.store');

// Public reading routes. Keep these LAST: they are catch-alls for /{section} and /{section}/{story}.
Route::get('search', Site\SearchController::class)->name('site.search');
Route::get('{categorySlug}', Site\CategoryController::class)
    ->where('categorySlug', '[a-z0-9-]+')->name('site.category');
Route::get('{categorySlug}/{articleSlug}', Site\ArticleController::class)
    ->where(['categorySlug' => '[a-z0-9-]+', 'articleSlug' => '[a-z0-9-]+'])->name('site.article');
