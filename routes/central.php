<?php

use App\Http\Controllers\CentralAdmin\Backup\SnapshotDownloadController;
use App\Http\Controllers\CentralAdmin\CentralBrandDetailController;
use App\Http\Controllers\CentralAdmin\CentralBrandFormController;
use App\Http\Controllers\CentralAdmin\CentralBrandLifecycleController;
use App\Http\Controllers\CentralAdmin\CentralBrandListController;
use App\Http\Controllers\CentralAdmin\CentralBrandMediaController;
use App\Http\Controllers\CentralAdmin\DesignSystem\ComponentGalleryController;
use App\Http\Controllers\CentralAdmin\Media\MediaAssetDetailController;
use App\Http\Controllers\CentralAdmin\Media\MediaLibraryController;
use App\Http\Controllers\CentralAdmin\Media\MediaUploadController;
use App\Http\Controllers\CentralAdmin\Media\ProductMediaManagerController;
use App\Http\Controllers\CentralAdmin\MissingTranslationsController;
use App\Http\Controllers\CentralAdmin\OutdatedTranslationsController;
use App\Http\Controllers\CentralAdmin\TranslationDashboardController;
use App\Http\Controllers\CentralAdmin\TranslationEditorController;
use App\Http\Middleware\EnsureCentralAdminAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureCentralAdminAccess::class])
    ->prefix('admin/central')
    ->group(function (): void {
        Route::middleware('can:catalog.products.manage')->group(function (): void {
            Route::get('/brands', CentralBrandListController::class)
                ->name('central.brands.index');
            Route::get('/brands/create', [CentralBrandFormController::class, 'create'])
                ->name('central.brands.create');
            Route::post('/brands', [CentralBrandFormController::class, 'store'])
                ->name('central.brands.store');
            Route::get('/brands/{brand}', CentralBrandDetailController::class)
                ->name('central.brands.show');
            Route::get('/brands/{brand}/media', [CentralBrandMediaController::class, 'show'])->name('central.brands.media');
            Route::post('/brands/{brand}/media/logo', [CentralBrandMediaController::class, 'storeLogo'])->name('central.brands.media.logo.store');
            Route::delete('/brands/{brand}/media/logo', [CentralBrandMediaController::class, 'destroyLogo'])->name('central.brands.media.logo.destroy');
            Route::get('/brands/{brand}/edit', [CentralBrandFormController::class, 'edit'])
                ->name('central.brands.edit');
            Route::patch('/brands/{brand}', [CentralBrandFormController::class, 'update'])
                ->name('central.brands.update');
            Route::post('/brands/{brand}/activate', [CentralBrandLifecycleController::class, 'activate'])
                ->name('central.brands.activate');
            Route::post('/brands/{brand}/archive', [CentralBrandLifecycleController::class, 'archive'])
                ->name('central.brands.archive');
            Route::post('/brands/{brand}/restore', [CentralBrandLifecycleController::class, 'restore'])
                ->name('central.brands.restore');
        });

        if (app()->environment(['local', 'testing'])) {
            Route::get('/component-gallery', ComponentGalleryController::class)
                ->name('central.component-gallery');
        }

        Route::middleware('can:translations.manage')->group(function (): void {
            Route::get('/translations/dashboard', TranslationDashboardController::class)
                ->name('central.translations.dashboard');
            Route::get('/translations/missing', MissingTranslationsController::class)
                ->name('central.translations.missing');
            Route::get('/translations/outdated', OutdatedTranslationsController::class)
                ->name('central.translations.outdated');

            Route::get('/products/{product}/translations/{locale}', [TranslationEditorController::class, 'editProduct'])
                ->name('central.products.translations.edit');
            Route::post('/products/{product}/translations/{locale}', [TranslationEditorController::class, 'saveProduct'])
                ->name('central.products.translations.save');

            Route::get('/categories/{category}/translations/{locale}', [TranslationEditorController::class, 'editCategory'])
                ->name('central.categories.translations.edit');
            Route::post('/categories/{category}/translations/{locale}', [TranslationEditorController::class, 'saveCategory'])
                ->name('central.categories.translations.save');

            Route::get('/attributes/{attribute}/translations/{locale}', [TranslationEditorController::class, 'editAttribute'])
                ->name('central.attributes.translations.edit');
            Route::post('/attributes/{attribute}/translations/{locale}', [TranslationEditorController::class, 'saveAttribute'])
                ->name('central.attributes.translations.save');

            Route::get('/attribute-sections/{section}/translations/{locale}', [TranslationEditorController::class, 'editSection'])
                ->name('central.attribute-sections.translations.edit');
            Route::post('/attribute-sections/{section}/translations/{locale}', [TranslationEditorController::class, 'saveSection'])
                ->name('central.attribute-sections.translations.save');

            Route::get('/attribute-options/{option}/translations/{locale}', [TranslationEditorController::class, 'editOption'])
                ->name('central.attribute-options.translations.edit');
            Route::post('/attribute-options/{option}/translations/{locale}', [TranslationEditorController::class, 'saveOption'])
                ->name('central.attribute-options.translations.save');

            Route::get('/units/{unit}/translations/{locale}', [TranslationEditorController::class, 'editUnit'])
                ->name('central.units.translations.edit');
            Route::post('/units/{unit}/translations/{locale}', [TranslationEditorController::class, 'saveUnit'])
                ->name('central.units.translations.save');
        });

        Route::get('/snapshots/{snapshot}/download/{fileKey}', SnapshotDownloadController::class)
            ->where('fileKey', '[A-Za-z0-9_-]+')
            ->name('central.snapshots.download');

        Route::middleware('can:media.manage')->group(function (): void {
            Route::get('/media', MediaLibraryController::class)
                ->name('central.media.index');
            Route::post('/media/upload', MediaUploadController::class)
                ->name('central.media.upload');
            Route::get('/media/{asset}', [MediaAssetDetailController::class, 'show'])
                ->name('central.media.show');
            Route::post('/media/{asset}/source', [MediaAssetDetailController::class, 'updateSource'])
                ->name('central.media.source.update');

            Route::get('/products/{product}/media', [ProductMediaManagerController::class, 'show'])
                ->name('central.products.media');
            Route::post('/products/{product}/media/assign', [ProductMediaManagerController::class, 'assign'])
                ->name('central.products.media.assign');
        });
    });

Route::middleware(['auth', EnsureCentralAdminAccess::class, 'can:media.manage'])
    ->prefix('central')
    ->group(function (): void {
        Route::get('/media', fn () => redirect()->route(
            'central.media.index',
            request()->query(),
        ))
            ->name('legacy.central.media.index');
        Route::get('/products/{product}/media', fn (string $product) => redirect()->route(
            'central.products.media',
            [...request()->query(), 'product' => $product],
        ))->name('legacy.central.products.media');
    });
