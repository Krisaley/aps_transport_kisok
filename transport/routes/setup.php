<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'account.is_active'])->group(function () {

    // customer management
    Route::livewire('setup/customers', 'pages::setup.customers.index')
        ->middleware('can:setup.area,customer')
        ->name('setup.customers.index');
    Route::livewire('setup/customers/create', 'pages::setup.customers.create')
        ->middleware('can:setup.customers.create')
        ->name('setup.customers.create');
    Route::livewire('setup/customers/{customer}/update', 'pages::setup.customers.update')
        ->middleware('can:setup.customers.update')
        ->name('setup.customers.update');

    Route::livewire('setup', 'pages::setup.dashboard')
        ->middleware('can:system.setup')
        ->name('setup.dashboard');

    Route::livewire('setup/makes', 'pages::setup.makes.index')->middleware('can:setup.area,make')->name('setup.makes.index');
    Route::livewire('setup/makes/create', 'pages::setup.makes.create')->middleware('can:setup.make.create')->name('setup.makes.create');
    Route::livewire('setup/makes/{make}/update', 'pages::setup.makes.update')->middleware('can:setup.make.update')->name('setup.makes.update');

    foreach (['model' => 'models', 'site' => 'sites', 'equipment' => 'equipment', 'vehicle' => 'vehicles'] as $area => $path) {
        Route::livewire("setup/{$path}", "pages::setup.{$path}.index")
            ->middleware("can:setup.area,{$area}")
            ->name("setup.{$path}.index");
        Route::livewire("setup/{$path}/create", "pages::setup.{$path}.form")
            ->middleware("can:setup.{$area}.create")
            ->name("setup.{$path}.create");
        Route::livewire("setup/{$path}/{{$area}}/update", "pages::setup.{$path}.form")
            ->middleware("can:setup.{$area}.update")
            ->name("setup.{$path}.update");
    }
});
