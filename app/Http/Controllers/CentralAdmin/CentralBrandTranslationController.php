<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\Translations\SaveBrandTranslationAction;
use App\Data\Translations\BrandTranslationEditorData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\Translations\SaveBrandTranslationRequest;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Queries\Translations\BrandTranslationEditorQuery;
use App\Services\Translations\AllowedTranslationStatuses;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CentralBrandTranslationController extends Controller
{
    public function index(
        CentralBrand $brand,
        BrandTranslationEditorQuery $query,
        AllowedTranslationStatuses $statuses,
    ): View|RedirectResponse {
        $editor = $query->forBrand($brand);
        $locale = $editor->locales->first();

        if ($locale instanceof Locale) {
            return redirect()->route('central.brands.translations.edit', [$brand, $locale->code]);
        }

        return $this->view($editor, $statuses);
    }

    public function edit(
        CentralBrand $brand,
        Locale $locale,
        BrandTranslationEditorQuery $query,
        AllowedTranslationStatuses $statuses,
    ): View {
        abort_unless($locale->is_active, 404);

        return $this->view($query->forBrand($brand, $locale), $statuses);
    }

    public function save(
        SaveBrandTranslationRequest $request,
        CentralBrand $brand,
        Locale $locale,
        SaveBrandTranslationAction $action,
    ): RedirectResponse {
        abort_unless($locale->is_active, 404);
        $action->handle($brand, $locale, $request->brandTranslationInput());

        return redirect()
            ->route('central.brands.translations.edit', [$brand, $locale->code])
            ->with('success', 'Translation saved.');
    }

    private function view(BrandTranslationEditorData $editor, AllowedTranslationStatuses $statuses): View
    {
        return view('central-admin.brands.translations', [
            'editor' => $editor,
            'brand' => $editor->brand,
            'locales' => $editor->locales,
            'translationsByLocale' => $editor->translationsByLocale,
            'selectedLocale' => $editor->selectedLocale,
            'translation' => $editor->translation,
            'statusOptions' => $statuses->optionsFor($editor->translation),
        ]);
    }
}
