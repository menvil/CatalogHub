<?php

namespace App\Http\Controllers\CentralAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\Translations\OutdatedTranslationsRequest;
use App\Queries\Translations\OutdatedTranslationsQuery;
use Illuminate\View\View;

final class OutdatedTranslationsController extends Controller
{
    public function __invoke(OutdatedTranslationsRequest $request, OutdatedTranslationsQuery $query): View
    {
        $filters = $request->filters();

        return view('central-admin.translations.outdated', [
            'items' => $query->get(
                locale: $filters->locale,
                entityType: $filters->entityType,
            ),
        ]);
    }
}
