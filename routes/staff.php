<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Category;
use App\Http\Controllers\Editor;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::resource('staff', Admin\StaffController::class)
        ->parameters(['staff' => 'user'])->except(['show', 'destroy']);
});

Route::middleware(['auth', 'role:editor_in_chief'])->prefix('editor')->name('editor.')->group(function () {
    Route::get('/', Editor\DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'role:category_head'])->prefix('category')->name('category.')->group(function () {
    Route::get('/', Category\DashboardController::class)->name('dashboard');
});
