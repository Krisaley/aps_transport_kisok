<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.admin', 'account.is_active'])->group(function () {

    // settings dashboard
    Route::livewire('settings/dashboard', 'pages::settings.dashboard')
        ->middleware('can:system.admin')
        ->name('settings.dashboard');

    // application configuration area
    Route::livewire('settings/configuration', 'pages::settings.config.index')
        ->middleware('can:admin.conf.update')
        ->name('settings.config');
    Route::livewire('settings/companies', 'pages::settings.companies.index')
        ->middleware('can:admin.company.view')
        ->name('settings.companies.index');
    Route::livewire('settings/companies/create', 'pages::settings.companies.form')
        ->middleware('can:admin.company.create')
        ->name('settings.companies.create');
    Route::livewire('settings/companies/{company}/update', 'pages::settings.companies.form')
        ->middleware('can:admin.company.update')
        ->name('settings.companies.update');

    // user management area
    Route::livewire('settings/users', 'pages::settings.users.index')
        ->middleware("can:admin.area,'user'")
        ->name('settings.users.index');
    Route::livewire('settings/users/create', 'pages::settings.users.create')
        ->middleware('can:admin.user.create')
        ->name('settings.users.create');
    Route::livewire('settings/users/{user}/update', 'pages::settings.users.update')
        ->middleware('can:admin.user.update')
        ->name('settings.users.update');

    // role management area
    Route::livewire('settings/roles', 'pages::settings.roles.index')
        ->middleware("can:admin.area,'role'")
        ->name('settings.roles.index');
    Route::livewire('settings/roles/create', 'pages::settings.roles.create')
        ->middleware('can:admin.role.create')
        ->name('settings.roles.create');
    Route::livewire('settings/roles/{role}/update', 'pages::settings.roles.update')
        ->middleware('can:admin.role.update')
        ->name('settings.roles.update');
    Route::livewire('settings/roles/{role}/permissions', 'pages::settings.roles.permissions')
        ->middleware('can:admin.role.update')
        ->name('settings.roles.permissions');
});
