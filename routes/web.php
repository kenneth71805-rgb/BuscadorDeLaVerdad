<?php

use App\Http\Controllers\DashboardRedirectController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('dashboard', DashboardRedirectController::class)->middleware('auth')->name('dashboard');

require __DIR__.'/staff.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
