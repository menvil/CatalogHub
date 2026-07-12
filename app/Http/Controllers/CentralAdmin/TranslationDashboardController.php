<?php

namespace App\Http\Controllers\CentralAdmin;

use App\Http\Controllers\Controller;
use App\Services\Translations\TranslationStatsService;
use Illuminate\View\View;

final class TranslationDashboardController extends Controller
{
    public function __invoke(TranslationStatsService $stats): View
    {
        return view('central-admin.translations.dashboard', [
            'stats' => $stats->dashboard(),
        ]);
    }
}
