<?php

namespace App\Http\Controllers\CentralAdmin;

use App\Http\Controllers\Controller;
use App\Queries\Translations\OutdatedTranslationsQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class OutdatedTranslationsController extends Controller
{
    public function __invoke(Request $request, OutdatedTranslationsQuery $query): View
    {
        return view('central-admin.translations.outdated', [
            'items' => $query->get(
                locale: $request->string('locale')->toString() ?: null,
                entityType: $request->string('entity_type')->toString() ?: null,
            ),
        ]);
    }
}
