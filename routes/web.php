<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\EntryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', [EntryController::class, 'index'])->name('timeline');

    Route::post('/entries', [EntryController::class, 'store'])->name('entries.store');
    Route::get('/entries/{entry}', [EntryController::class, 'show'])->name('entries.show');
    Route::put('/entries/{entry}', [EntryController::class, 'update'])->name('entries.update');
    Route::delete('/entries/{entry}', [EntryController::class, 'destroy'])->name('entries.destroy');
    Route::post('/entries/{entry}/restore', [EntryController::class, 'restore'])
        ->withTrashed()
        ->name('entries.restore');

    Route::post('/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
});
