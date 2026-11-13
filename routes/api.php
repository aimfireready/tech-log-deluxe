<?php

use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\EntryController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TokenController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/tokens', [TokenController::class, 'store'])
        ->middleware('throttle:login')
        ->name('tokens.store');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('/tokens/current', [TokenController::class, 'destroy'])->name('tokens.destroy');
        Route::get('/user', UserController::class)->name('user');

        Route::apiResource('entries', EntryController::class);
        Route::post('/entries/{entry}/restore', [EntryController::class, 'restore'])
            ->withTrashed()
            ->name('entries.restore');

        Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
        Route::get('/search', SearchController::class)->name('search');
        Route::post('/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    });
});
