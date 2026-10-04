<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\Translations\ApproveBrandTranslationAction;
use App\Actions\Translations\MarkBrandTranslationOutdatedAction;
use App\Actions\Translations\SaveBrandTranslationAction;
use App\Data\Translations\BrandTranslationEditorData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\Translations\BrandTranslationSourceRequest;
use App\Http\Requests\CentralAdmin\Translations\SaveBrandTranslationRequest;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\User;
use App\Queries\Translations\BrandTranslationEditorQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CentralBrandTranslationController extends Controller
{
    public function index(
        CentralBrand $brand,
        BrandTranslationEditorQuery $query,
    ): View|RedirectResponse {
        $editor = $query->forBrand($brand);
        $locale = $editor->locales->first();

        if ($locale instanceof Locale) {
            return redirect()->route('central.brands.translations.edit', [$brand, $locale->code]);
        }

        return $this->view($editor);
    }

    public function edit(
        BrandTranslationSourceRequest $request,
        CentralBrand $brand,
        Locale $locale,
        BrandTranslationEditorQuery $query,
    ): View {
        abort_unless($locale->is_active, 404);

        return $this->view($query->forBrand($brand, $locale, $this->sourceCode($request)));
    }

    public function save(
        SaveBrandTranslationRequest $request,
        CentralBrand $brand,
        Locale $locale,
        SaveBrandTranslationAction $action,
    ): RedirectResponse {
        abort_unless($locale->is_active, 404);
        $sourceCode = $this->sourceCode($request);
        $actor = $request->user();
        assert($actor instanceof User);
        $action->handle($actor, $brand, $locale, $request->brandTranslationInput());

        return redirect()
            ->route('central.brands.translations.edit', [$brand, $locale->code, ...($sourceCode !== null ? ['source' => $sourceCode] : [])])
            ->with('success', 'Translation saved.');
    }

    public function approve(
        BrandTranslationSourceRequest $request,
        CentralBrand $brand,
        Locale $locale,
        ApproveBrandTranslationAction $action,
    ): RedirectResponse {
        abort_unless($locale->is_active, 404);
        $sourceCode = $this->sourceCode($request);
        $actor = $request->user();
        assert($actor instanceof User);
        $action->handle($actor, $brand, $locale);

        return redirect()
            ->route('central.brands.translations.edit', [$brand, $locale->code, ...($sourceCode !== null ? ['source' => $sourceCode] : [])])
            ->with('success', 'Translation approved.');
    }

    public function markOutdated(
        BrandTranslationSourceRequest $request,
        CentralBrand $brand,
        Locale $locale,
        MarkBrandTranslationOutdatedAction $action,
    ): RedirectResponse {
        abort_unless($locale->is_active, 404);
        $sourceCode = $this->sourceCode($request);
        $actor = $request->user();
        assert($actor instanceof User);
        $action->handle($actor, $brand, $locale);

        return redirect()
            ->route('central.brands.translations.edit', [$brand, $locale->code, ...($sourceCode !== null ? ['source' => $sourceCode] : [])])
            ->with('success', 'Translation marked outdated.');
    }

    private function sourceCode(FormRequest $request): ?string
    {
        $code = $request->validated('source');
        assert($code === null || is_string($code));

        return $code;
    }

    private function view(BrandTranslationEditorData $editor): View
    {
        return view('central-admin.brands.translations', [
            'editor' => $editor,
            'brand' => $editor->brand,
            'locales' => $editor->locales,
            'translationsByLocale' => $editor->translationsByLocale,
            'selectedLocale' => $editor->selectedLocale,
            'translation' => $editor->translation,
            'sourceLocale' => $editor->sourceLocale,
            'sourceTranslation' => $editor->sourceTranslation,
            'sourceLocales' => $editor->sourceLocales,
            'currentSourceHash' => $editor->currentSourceHash,
            'sourceHashMatches' => $editor->sourceHashMatches,
            'activity' => $editor->activity,
        ]);
    }
}
