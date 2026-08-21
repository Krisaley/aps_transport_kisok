<?php

use App\Http\Controllers\PwaController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'account.is_active'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('pwa', [PwaController::class, 'index'])->name('pwa.index');
    Route::get('pwa/bootstrap', [PwaController::class, 'bootstrap'])->name('pwa.bootstrap');
    Route::post('pwa/sync', [PwaController::class, 'sync'])->name('pwa.sync');
});

require __DIR__.'/profile.php';
require __DIR__.'/settings.php';
require __DIR__.'/operations.php';
require __DIR__.'/setup.php';
