<?php

use App\Http\Controllers\MovementDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:system.operations', 'account.is_active'])->group(function () {

    Route::get('operations/movements/{movement}/documents/{type}/preview', [MovementDocumentController::class, 'preview'])->name('documents.preview');
    Route::post('operations/movements/{movement}/documents/{type}', [MovementDocumentController::class, 'issue'])->name('documents.issue');
    Route::get('operations/documents/{document}/download', [MovementDocumentController::class, 'download'])->name('documents.download');
    Route::livewire('operations/movements', 'pages::operations.movements.index')
        ->middleware("can:operations.area,'movement'")
        ->name('operations.movements.index');
    Route::livewire('operations/movements/create', 'pages::operations.movements.form')
        ->middleware('can:operations.movement.create')
        ->name('operations.movements.create');
    Route::livewire('operations/movements/{movement}/update', 'pages::operations.movements.form')
        ->middleware('can:operations.movement.update')
        ->name('operations.movements.update');
});
