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
        $filters = $request->validate([
            'locale' => ['nullable', 'string', 'max:20'],
            'entity_type' => ['nullable', 'string', 'in:product,category,attribute,section,option,unit'],
        ]);

        return view('central-admin.translations.outdated', [
            'items' => $query->get(
                locale: $filters['locale'] ?? null,
                entityType: $filters['entity_type'] ?? null,
            ),
        ]);
    }
}
