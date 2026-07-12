<?php

namespace App\Http\Controllers\CentralAdmin;

use App\Http\Controllers\Controller;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TranslationDashboardController extends Controller
{
    public function __invoke(Request $request, TranslationStatsService $stats): View
    {
        abort_unless($request->user()?->hasCatalogHubPermission('translations.manage'), 403);

        return view('central-admin.translations.dashboard', [
            'stats' => $stats->dashboard(),
        ]);
    }
}
