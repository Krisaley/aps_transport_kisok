<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:system.transport', 'account.is_active'])->group(function () {
    Route::livewire('transport/vehicles', 'pages::transport.vehicles.index')
        ->middleware("can:transport.area,'vehicle'")
        ->name('transport.vehicles.index');
    Route::livewire('transport/vehicles/create', 'pages::transport.vehicles.form')
        ->middleware('can:transport.vehicle.create')
        ->name('transport.vehicles.create');
    Route::livewire('transport/vehicles/{vehicle}/update', 'pages::transport.vehicles.form')
        ->middleware('can:transport.vehicle.update')
        ->name('transport.vehicles.update');
});
