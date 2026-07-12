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
        abort_unless($request->user()?->hasCatalogHubPermission('translations.manage'), 403);

        $filters = $request->validate([
            'locale' => ['nullable', 'string', 'max:20'],
            'entity_type' => ['nullable', 'string', 'in:product,category,attribute,section,option,unit'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        return view('central-admin.translations.missing', [
            'items' => $query->get(
                locale: $filters['locale'] ?? null,
                entityType: $filters['entity_type'] ?? null,
                search: $filters['search'] ?? null,
            ),
        ]);
    }
}
