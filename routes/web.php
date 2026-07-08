<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/health.php';

Route::get('/', function () {
    return view('pages.home');
});

if (app()->environment(['local', 'testing'])) {
    Route::get('/dev/ui-kit', function () {
        return view('dev.ui-kit');
    })->name('dev.ui-kit');

    Route::get('/dev/admin-visual-smoke', function () {
        return view('dev.admin-visual-smoke');
    })->name('dev.admin-visual-smoke');
}
