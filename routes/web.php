<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\ReaderController;
use App\Http\Middleware\AllowReaderKey;
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

// Read-only, blog-style reading view: signed-in users, or anyone with the share key.
Route::middleware(AllowReaderKey::class)
    ->prefix('read')->name('reader.')->controller(ReaderController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/tags/{tag:slug}', 'tag')->name('tag');
        Route::get('/entries/{entry}', 'show')->name('show');
    });
