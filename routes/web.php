<?php

use App\Http\Controllers\CentralAdmin\DesignSystem\CentralShellPreviewController;
use App\Http\Controllers\CentralAdmin\DesignSystem\ComponentGalleryController;
use App\Http\Controllers\Public\DesignSystem\PublicShellPreviewController;
use App\Http\Controllers\SiteAdmin\DesignSystem\SiteAdminShellPreviewController;
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

    Route::get('/dev/site-admin-shell', SiteAdminShellPreviewController::class)
        ->name('dev.site-admin-shell.capture');

    Route::get('/dev/public-shell', PublicShellPreviewController::class)
        ->name('public.dev-shell.capture');

    Route::get('/dev/system-error', static function () {
        return response()->view('errors.admin.500', [
            'presentationContext' => 'central-admin',
            'dashboardUrl' => '/admin/central',
            'dashboardLabel' => 'Return to Central Admin',
            'requestId' => '00000000-0000-4000-8000-000000000007',
        ], 500);
    })->name('dev.system-error.capture');
}
