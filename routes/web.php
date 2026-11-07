<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => Inertia::render('timeline/index'))->name('timeline');
});
