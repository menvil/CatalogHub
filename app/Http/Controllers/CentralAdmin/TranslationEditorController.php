<?php

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\Translations\SaveAttributeOptionTranslationAction;
use App\Actions\Translations\SaveAttributeSectionTranslationAction;
use App\Actions\Translations\SaveAttributeTranslationAction;
use App\Actions\Translations\SaveCategoryTranslationAction;
use App\Actions\Translations\SaveProductTranslationAction;
use App\Actions\Translations\SaveUnitTranslationAction;
use App\Enums\TranslationStatus;
use App\Http\Controllers\Controller;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TranslationEditorController extends Controller
{
    public function editProduct(Request $request, CentralProduct $product, Locale $locale): View
    {
        $this->authorizeTranslations($request);

        return view('central-admin.translations.editor', [
            'title' => 'Product Translation Editor',
            'sourceLabel' => $product->name,
            'locale' => $locale,
            'translation' => $product->translations()->where('locale', $locale->code)->first(),
            'fields' => ['name', 'subtitle', 'short_description', 'description', 'seo_title', 'seo_description'],
            'saveRoute' => route('central.products.translations.save', [$product, $locale]),
        ]);
    }

    public function saveProduct(Request $request, CentralProduct $product, Locale $locale, SaveProductTranslationAction $action): RedirectResponse
    {
        $this->authorizeTranslations($request);

        $action->handle($product, $locale, $request->validate($this->rules([
            'name' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ])));

        return back()->with('status', 'Translation saved.');
    }

    public function editCategory(Request $request, CentralCategory $category, Locale $locale): View
    {
        $this->authorizeTranslations($request);

        return view('central-admin.translations.editor', [
            'title' => 'Category Translation Editor',
            'sourceLabel' => $category->name,
            'locale' => $locale,
            'translation' => $category->translations()->where('locale', $locale->code)->first(),
            'fields' => ['name', 'description', 'seo_title', 'seo_description'],
            'saveRoute' => route('central.categories.translations.save', [$category, $locale]),
        ]);
    }

    public function saveCategory(Request $request, CentralCategory $category, Locale $locale, SaveCategoryTranslationAction $action): RedirectResponse
    {
        $this->authorizeTranslations($request);

        $action->handle($category, $locale, $request->validate($this->rules([
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ])));

        return back()->with('status', 'Translation saved.');
    }

    public function editAttribute(Request $request, AttributeDefinition $attribute, Locale $locale): View
    {
        $this->authorizeTranslations($request);

        return view('central-admin.translations.editor', [
            'title' => 'Attribute Translation Editor',
            'sourceLabel' => $attribute->name,
            'locale' => $locale,
            'translation' => $attribute->translations()->where('locale', $locale->code)->first(),
            'fields' => ['label', 'short_label', 'help_text'],
            'saveRoute' => route('central.attributes.translations.save', [$attribute, $locale]),
        ]);
    }

    public function saveAttribute(Request $request, AttributeDefinition $attribute, Locale $locale, SaveAttributeTranslationAction $action): RedirectResponse
    {
        $this->authorizeTranslations($request);

        $action->handle($attribute, $locale, $request->validate($this->rules([
            'label' => ['nullable', 'string', 'max:255'],
            'short_label' => ['nullable', 'string', 'max:100'],
            'help_text' => ['nullable', 'string', 'max:2000'],
        ])));

        return back()->with('status', 'Translation saved.');
    }

    public function editSection(Request $request, AttributeSection $section, Locale $locale): View
    {
        $this->authorizeTranslations($request);

        return view('central-admin.translations.editor', [
            'title' => 'Attribute Section Translation Editor',
            'sourceLabel' => $section->name,
            'locale' => $locale,
            'translation' => $section->translations()->where('locale', $locale->code)->first(),
            'fields' => ['name', 'description'],
            'saveRoute' => route('central.attribute-sections.translations.save', [$section, $locale]),
        ]);
    }

    public function saveSection(Request $request, AttributeSection $section, Locale $locale, SaveAttributeSectionTranslationAction $action): RedirectResponse
    {
        $this->authorizeTranslations($request);

        $action->handle($section, $locale, $request->validate($this->rules([
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ])));

        return back()->with('status', 'Translation saved.');
    }

    public function editOption(Request $request, AttributeOption $option, Locale $locale): View
    {
        $this->authorizeTranslations($request);

        return view('central-admin.translations.editor', [
            'title' => 'Attribute Option Translation Editor',
            'sourceLabel' => $option->label,
            'locale' => $locale,
            'translation' => $option->translations()->where('locale', $locale->code)->first(),
            'fields' => ['label', 'description'],
            'saveRoute' => route('central.attribute-options.translations.save', [$option, $locale]),
        ]);
    }

    public function saveOption(Request $request, AttributeOption $option, Locale $locale, SaveAttributeOptionTranslationAction $action): RedirectResponse
    {
        $this->authorizeTranslations($request);

        $action->handle($option, $locale, $request->validate($this->rules([
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ])));

        return back()->with('status', 'Translation saved.');
    }

    public function editUnit(Request $request, MeasurementUnit $unit, Locale $locale): View
    {
        $this->authorizeTranslations($request);

        return view('central-admin.translations.editor', [
            'title' => 'Unit Translation Editor',
            'sourceLabel' => $unit->name,
            'locale' => $locale,
            'translation' => $unit->translations()->where('locale', $locale->code)->first(),
            'fields' => ['short_name', 'long_name', 'plural_name', 'symbol_position', 'space_between_value_and_unit'],
            'saveRoute' => route('central.units.translations.save', [$unit, $locale]),
            'preview' => '100 '.($unit->symbol ?? $unit->code),
        ]);
    }

    public function saveUnit(Request $request, MeasurementUnit $unit, Locale $locale, SaveUnitTranslationAction $action): RedirectResponse
    {
        $this->authorizeTranslations($request);

        $action->handle($unit, $locale, $request->validate($this->rules([
            'short_name' => ['nullable', 'string', 'max:100'],
            'long_name' => ['nullable', 'string', 'max:255'],
            'plural_name' => ['nullable', 'string', 'max:255'],
            'symbol_position' => ['nullable', 'string', 'in:before,after'],
            'space_between_value_and_unit' => ['nullable', 'boolean'],
        ])));

        return back()->with('status', 'Translation saved.');
    }

    private function authorizeTranslations(Request $request): void
    {
        abort_unless($request->user()?->hasCatalogHubPermission('translations.manage'), 403);
    }

    /**
     * @param  array<string, list<string>>  $fieldRules
     * @return array<string, list<string>>
     */
    private function rules(array $fieldRules): array
    {
        return $fieldRules + [
            'status' => [
                'nullable',
                'string',
                'in:'.collect(TranslationStatus::cases())
                    ->reject(fn (TranslationStatus $status): bool => $status === TranslationStatus::Approved)
                    ->pluck('value')
                    ->implode(','),
            ],
        ];
    }
}
