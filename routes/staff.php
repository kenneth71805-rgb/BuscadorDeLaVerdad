<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Newsroom;
use App\Http\Controllers\Category;
use App\Http\Controllers\Editor;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::resource('staff', Admin\StaffController::class)
        ->parameters(['staff' => 'user'])->except(['show', 'destroy']);
    Route::resource('categories', Admin\CategoryController::class)->except(['show', 'destroy']);
    Route::resource('profiles', Admin\ProfileController::class)->except(['show', 'destroy']);
    Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
});

Route::middleware(['auth', 'role:editor_in_chief'])->prefix('editor')->name('editor.')->group(function () {
    Route::get('/', Editor\DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'role:category_head'])->prefix('category')->name('category.')->group(function () {
    Route::get('/', Category\DashboardController::class)->name('dashboard');
});

// Shared newsroom workspace; every action is authorized by ArticlePolicy and ArticleWorkflow.
Route::middleware(['auth', 'role:admin,editor_in_chief,category_head'])->group(function () {
    Route::resource('articles', ArticleController::class)->except(['destroy']);
    Route::post('articles/{article}/transition', [ArticleController::class, 'transition'])->name('articles.transition');
});

Route::middleware(['auth', 'role:admin,editor_in_chief'])->prefix('inbox')->name('inbox.')->group(function () {
    Route::get('/', [InboxController::class, 'index'])->name('index');
    Route::get('{submission}', [InboxController::class, 'show'])->name('show');
    Route::put('{submission}', [InboxController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'role:admin,editor_in_chief,category_head'])->prefix('media')->name('media.')->group(function () {
    Route::get('/', [MediaController::class, 'index'])->name('index');
    Route::post('/', [MediaController::class, 'store'])->name('store');
    Route::put('{media}', [MediaController::class, 'update'])->name('update');
    Route::put('{media}/release', [MediaController::class, 'release'])->name('release');
    Route::delete('{media}', [MediaController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'role:admin,editor_in_chief'])->prefix('newsroom')->name('newsroom.')->group(function () {
    Route::resource('galleries', Newsroom\GalleryController::class)->except(['show', 'destroy']);
    Route::resource('issues', Newsroom\IssueController::class)->except(['show', 'destroy']);
});
