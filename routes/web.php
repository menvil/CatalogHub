<?php

use App\Http\Controllers\CentralAdmin\Media\MediaAssetDetailController;
use App\Http\Controllers\CentralAdmin\Media\MediaLibraryController;
use App\Http\Controllers\CentralAdmin\Media\MediaUploadController;
use App\Http\Controllers\CentralAdmin\Media\ProductMediaManagerController;
use App\Http\Controllers\CentralAdmin\MissingTranslationsController;
use App\Http\Controllers\CentralAdmin\OutdatedTranslationsController;
use App\Http\Controllers\CentralAdmin\TranslationDashboardController;
use App\Http\Controllers\CentralAdmin\TranslationEditorController;
use App\Http\Controllers\Public\CategoryController as PublicCategoryController;
use App\Http\Controllers\Public\HomeController as PublicHomeController;
use App\Http\Controllers\Public\ProductController as PublicProductController;
use App\Http\Controllers\Public\ProductListingController as PublicProductListingController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/health.php';

Route::get('/', function () {
    return view('pages.home');
});

Route::get('/{locale}', PublicHomeController::class)
    ->where('locale', '[a-z]{2}(?:-[A-Z]{2})?')
    ->name('public.home');

Route::get('/{locale}/categories/{slug}', [PublicCategoryController::class, 'show'])
    ->where('locale', '[a-z]{2}(?:-[A-Z]{2})?')
    ->name('public.categories.show');

Route::get('/{locale}/categories/{slug}/products', PublicProductListingController::class)
    ->where('locale', '[a-z]{2}(?:-[A-Z]{2})?')
    ->name('public.categories.products');

Route::get('/{locale}/products/{slug}', [PublicProductController::class, 'show'])
    ->where('locale', '[a-z]{2}(?:-[A-Z]{2})?')
    ->name('public.products.show');

if (app()->environment(['local', 'testing'])) {
    Route::get('/dev/ui-kit', function () {
        return view('dev.ui-kit');
    })->name('dev.ui-kit');

    Route::get('/dev/admin-visual-smoke', function () {
        return view('dev.admin-visual-smoke');
    })->name('dev.admin-visual-smoke');
}

Route::middleware(['auth', 'can:translations.manage'])->prefix('admin')->group(function (): void {
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

Route::middleware('auth')->prefix('central')->group(function (): void {
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
