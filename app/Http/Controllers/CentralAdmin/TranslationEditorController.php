<?php

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\Translations\SaveAttributeOptionTranslationAction;
use App\Actions\Translations\SaveAttributeSectionTranslationAction;
use App\Actions\Translations\SaveAttributeTranslationAction;
use App\Actions\Translations\SaveCategoryTranslationAction;
use App\Actions\Translations\SaveProductTranslationAction;
use App\Actions\Translations\SaveUnitTranslationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\Translations\SaveAttributeOptionTranslationRequest;
use App\Http\Requests\CentralAdmin\Translations\SaveAttributeSectionTranslationRequest;
use App\Http\Requests\CentralAdmin\Translations\SaveAttributeTranslationRequest;
use App\Http\Requests\CentralAdmin\Translations\SaveCategoryTranslationRequest;
use App\Http\Requests\CentralAdmin\Translations\SaveProductTranslationRequest;
use App\Http\Requests\CentralAdmin\Translations\SaveUnitTranslationRequest;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Queries\Translations\TranslationEditorQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TranslationEditorController extends Controller
{
    public function __construct(private readonly TranslationEditorQuery $translations) {}

    public function editProduct(CentralProduct $product, Locale $locale): View
    {
        return view('central-admin.translations.editor', [
            'title' => 'Product Translation Editor',
            'sourceLabel' => $product->name,
            'locale' => $locale,
            'translation' => $this->translations->product($product, $locale->code),
            'fields' => ['name', 'subtitle', 'short_description', 'description', 'seo_title', 'seo_description'],
            'saveRoute' => route('central.products.translations.save', [$product, $locale]),
        ]);
    }

    public function saveProduct(SaveProductTranslationRequest $request, CentralProduct $product, Locale $locale, SaveProductTranslationAction $action): RedirectResponse
    {
        $action->handle($product, $locale, $request->payload());

        return back()->with('status', 'Translation saved.');
    }

    public function editCategory(CentralCategory $category, Locale $locale): View
    {
        return view('central-admin.translations.editor', [
            'title' => 'Category Translation Editor',
            'sourceLabel' => $category->name,
            'locale' => $locale,
            'translation' => $this->translations->category($category, $locale->code),
            'fields' => ['name', 'description', 'seo_title', 'seo_description'],
            'saveRoute' => route('central.categories.translations.save', [$category, $locale]),
        ]);
    }

    public function saveCategory(SaveCategoryTranslationRequest $request, CentralCategory $category, Locale $locale, SaveCategoryTranslationAction $action): RedirectResponse
    {
        $action->handle($category, $locale, $request->payload());

        return back()->with('status', 'Translation saved.');
    }

    public function editAttribute(AttributeDefinition $attribute, Locale $locale): View
    {
        return view('central-admin.translations.editor', [
            'title' => 'Attribute Translation Editor',
            'sourceLabel' => $attribute->name,
            'locale' => $locale,
            'translation' => $this->translations->attribute($attribute, $locale->code),
            'fields' => ['label', 'short_label', 'help_text'],
            'saveRoute' => route('central.attributes.translations.save', [$attribute, $locale]),
        ]);
    }

    public function saveAttribute(SaveAttributeTranslationRequest $request, AttributeDefinition $attribute, Locale $locale, SaveAttributeTranslationAction $action): RedirectResponse
    {
        $action->handle($attribute, $locale, $request->payload());

        return back()->with('status', 'Translation saved.');
    }

    public function editSection(AttributeSection $section, Locale $locale): View
    {
        return view('central-admin.translations.editor', [
            'title' => 'Attribute Section Translation Editor',
            'sourceLabel' => $section->name,
            'locale' => $locale,
            'translation' => $this->translations->section($section, $locale->code),
            'fields' => ['name', 'description'],
            'saveRoute' => route('central.attribute-sections.translations.save', [$section, $locale]),
        ]);
    }

    public function saveSection(SaveAttributeSectionTranslationRequest $request, AttributeSection $section, Locale $locale, SaveAttributeSectionTranslationAction $action): RedirectResponse
    {
        $action->handle($section, $locale, $request->payload());

        return back()->with('status', 'Translation saved.');
    }

    public function editOption(AttributeOption $option, Locale $locale): View
    {
        return view('central-admin.translations.editor', [
            'title' => 'Attribute Option Translation Editor',
            'sourceLabel' => $option->label,
            'locale' => $locale,
            'translation' => $this->translations->option($option, $locale->code),
            'fields' => ['label', 'description'],
            'saveRoute' => route('central.attribute-options.translations.save', [$option, $locale]),
        ]);
    }

    public function saveOption(SaveAttributeOptionTranslationRequest $request, AttributeOption $option, Locale $locale, SaveAttributeOptionTranslationAction $action): RedirectResponse
    {
        $action->handle($option, $locale, $request->payload());

        return back()->with('status', 'Translation saved.');
    }

    public function editUnit(MeasurementUnit $unit, Locale $locale): View
    {
        return view('central-admin.translations.editor', [
            'title' => 'Unit Translation Editor',
            'sourceLabel' => $unit->name,
            'locale' => $locale,
            'translation' => $this->translations->unit($unit, $locale->code),
            'fields' => ['short_name', 'long_name', 'plural_name', 'symbol_position', 'space_between_value_and_unit'],
            'saveRoute' => route('central.units.translations.save', [$unit, $locale]),
            'preview' => '100 '.($unit->symbol ?? $unit->code),
        ]);
    }

    public function saveUnit(SaveUnitTranslationRequest $request, MeasurementUnit $unit, Locale $locale, SaveUnitTranslationAction $action): RedirectResponse
    {
        $action->handle($unit, $locale, $request->payload());

        return back()->with('status', 'Translation saved.');
    }
}
