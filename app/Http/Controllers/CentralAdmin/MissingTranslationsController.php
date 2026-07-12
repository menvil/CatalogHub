<?php

namespace App\Http\Controllers\CentralAdmin;

use App\Http\Controllers\Controller;
use App\Queries\Translations\MissingTranslationsQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MissingTranslationsController extends Controller
{
    public function __invoke(Request $request, MissingTranslationsQuery $query): View
    {
        return view('central-admin.translations.missing', [
            'items' => $query->get(
                locale: $request->string('locale')->toString() ?: null,
                entityType: $request->string('entity_type')->toString() ?: null,
                search: $request->string('search')->toString() ?: null,
            ),
        ]);
    }
}
