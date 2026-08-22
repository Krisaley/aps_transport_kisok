<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:system.stock', 'account.is_active'])->group(function () {
    Route::livewire('stock/makes', 'pages::stock.makes.index')
        ->middleware("can:stock.area,'make-model'")
        ->name('stock.makes.index');
    Route::livewire('stock/makes/create', 'pages::stock.makes.create')
        ->middleware('can:stock.make-model.create')
        ->name('stock.makes.create');
    Route::livewire('stock/makes/{make}/update', 'pages::stock.makes.update')
        ->middleware('can:stock.make-model.update')
        ->name('stock.makes.update');
    Route::livewire('stock/makes/{make}/models', 'pages::stock.makes.models')
        ->middleware("can:stock.area,'make-model'")
        ->name('stock.makes.models');
    Route::livewire('stock/models/{model}/update', 'pages::stock.models.form')
        ->middleware('can:stock.make-model.update')
        ->name('stock.models.update');

    Route::livewire('stock/equipment', 'pages::stock.equipment.index')
        ->middleware("can:stock.area,'equipment'")
        ->name('stock.equipment.index');
    Route::livewire('stock/equipment/create', 'pages::stock.equipment.form')
        ->middleware('can:stock.equipment.create')
        ->name('stock.equipment.create');
    Route::livewire('stock/equipment/{equipment}/update', 'pages::stock.equipment.form')
        ->middleware('can:stock.equipment.update')
        ->name('stock.equipment.update');
});
