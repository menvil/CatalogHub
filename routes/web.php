<?php

use App\Http\Controllers\CentralAdmin\DesignSystem\CentralShellPreviewController;
use App\Http\Controllers\CentralAdmin\DesignSystem\ComponentGalleryController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/health.php';
require __DIR__.'/public.php';
require __DIR__.'/central.php';

Route::redirect('/admin', '/admin/central')->name('legacy.admin');

if (app()->environment(['local', 'testing'])) {
    Route::get('/dev/ui-kit', function () {
        return view('dev.ui-kit');
    })->name('dev.ui-kit');

    Route::get('/dev/admin-visual-smoke', function () {
        return view('dev.admin-visual-smoke');
    })->name('dev.admin-visual-smoke');

    Route::get('/dev/component-gallery', ComponentGalleryController::class)
        ->name('dev.component-gallery.capture');

    Route::get('/dev/central-shell', CentralShellPreviewController::class)
        ->name('dev.central-shell.capture');
}
