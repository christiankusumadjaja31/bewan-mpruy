<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::livewire('/habits', 'pages::habits.index')
    ->middleware(['auth', 'verified'])
    ->name('habits.index');

Route::livewire('/challenges', 'pages::challenges.index')
    ->middleware(['auth', 'verified'])
    ->name('challenges.index');

Route::livewire('/shop', 'pages::shop.index')
    ->middleware(['auth', 'verified'])
    ->name('shop.index');

require __DIR__.'/settings.php';
