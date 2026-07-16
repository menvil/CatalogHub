<?php

namespace App\Http\Controllers\CentralAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\Translations\MissingTranslationsRequest;
use App\Queries\Translations\MissingTranslationsQuery;
use Illuminate\View\View;

final class MissingTranslationsController extends Controller
{
    public function __invoke(MissingTranslationsRequest $request, MissingTranslationsQuery $query): View
    {
        $filters = $request->filters();

        return view('central-admin.translations.missing', [
            'items' => $query->get(
                locale: $filters->locale,
                entityType: $filters->entityType,
                search: $filters->search,
            ),
        ]);
    }
}
