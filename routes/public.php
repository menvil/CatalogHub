<?php

use App\Http\Controllers\Public\CategoryController as PublicCategoryController;
use App\Http\Controllers\Public\CompareController as PublicCompareController;
use App\Http\Controllers\Public\ContentController as PublicContentController;
use App\Http\Controllers\Public\HomeController as PublicHomeController;
use App\Http\Controllers\Public\ProductController as PublicProductController;
use App\Http\Controllers\Public\ProductListingController as PublicProductListingController;
use App\Http\Controllers\Public\SearchController as PublicSearchController;
use App\Http\Controllers\Public\TrackOfferClickController;
use App\Http\Middleware\ResolveSiteRuntimeContext;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.home');
})->name('public.landing');

Route::middleware(ResolveSiteRuntimeContext::class)->group(function (): void {
    Route::get('/offers/{offer}/go', TrackOfferClickController::class)
        ->whereNumber('offer')
        ->name('public.offers.go');

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

    Route::get('/{locale}/compare', PublicCompareController::class)
        ->where('locale', '[a-z]{2}(?:-[A-Z]{2})?')
        ->name('public.compare');

    Route::get('/{locale}/articles/{slug}', PublicContentController::class)
        ->where('locale', '[a-z]{2}(?:-[A-Z]{2})?')
        ->name('public.articles.show');

    Route::get('/{locale}/search', PublicSearchController::class)
        ->where('locale', '[a-z]{2}(?:-[A-Z]{2})?')
        ->middleware('throttle:public-search')
        ->name('public.search');

    if (app()->environment('testing')) {
        Route::get('/{locale}/__foundation-error/{status}', static function (string $locale, int $status): never {
            if ($status === 500) {
                throw new RuntimeException('database-password=secret');
            }

            abort($status, 'internal-maintenance-detail');
        })
            ->where('locale', '[a-z]{2}(?:-[A-Z]{2})?')
            ->whereNumber('status')
            ->name('public.foundation-error');
    }

    Route::get('/{locale}/{missing}', static function (): never {
        abort(404);
    })
        ->where('locale', '[a-z]{2}(?:-[A-Z]{2})?')
        ->where('missing', '.+')
        ->name('public.not-found');
});
