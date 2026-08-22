<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:system.crm', 'account.is_active'])->group(function () {

    // customer management
    Route::livewire('crm/customers', 'pages::crm.customers.index')
        ->middleware("can:crm.area,'customer'")
        ->name('crm.customers.index');
    Route::livewire('crm/customers/create', 'pages::crm.customers.create')
        ->middleware('can:crm.customer.create')
        ->name('crm.customers.create');
    Route::livewire('crm/customers/{customer}/update', 'pages::crm.customers.update')
        ->middleware('can:crm.customer.update')
        ->name('crm.customers.update');

    // site management
    Route::livewire('crm/sites', 'pages::crm.sites.index')
        ->middleware("can:crm.area,'site'")
        ->name('crm.sites.index');
    Route::livewire('crm/sites/create', 'pages::crm.sites.form')
        ->middleware('can:crm.site.create')
        ->name('crm.sites.create');
    Route::livewire('crm/sites/{site}/update', 'pages::crm.sites.form')
        ->middleware('can:crm.site.update')
        ->name('crm.sites.update');
});
