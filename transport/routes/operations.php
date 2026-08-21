<?php

use App\Http\Controllers\MovementDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'account.is_active'])->group(function () {
    Route::get('operations/movements/{movement}/documents/{type}/preview', [MovementDocumentController::class, 'preview'])->name('documents.preview');
    Route::post('operations/movements/{movement}/documents/{type}', [MovementDocumentController::class, 'issue'])->name('documents.issue');
    Route::get('operations/documents/{document}/download', [MovementDocumentController::class, 'download'])->name('documents.download');
    Route::livewire('operations/movements', 'pages::operations.movements.index')
        ->middleware("can:user.area,'movement'")
        ->name('operations.movements.index');
    Route::livewire('operations/movements/create', 'pages::operations.movements.form')
        ->middleware('can:user.movement.create')
        ->name('operations.movements.create');
    Route::livewire('operations/movements/{movement}/update', 'pages::operations.movements.form')
        ->middleware('can:user.movement.update')
        ->name('operations.movements.update');
});
