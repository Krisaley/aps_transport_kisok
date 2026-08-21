<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'account.is_active'])->group(function () {
    Route::livewire('profile', 'pages::profile.index')->name('profile.edit');
});

Route::middleware(['auth', 'verified', 'account.is_active'])->group(function () {
    Route::livewire('profile/appearance', 'pages::profile.appearance')->name('appearance.edit');

    Route::livewire('profile/security', 'pages::profile.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
