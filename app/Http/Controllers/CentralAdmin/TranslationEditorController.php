<?php

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\Translations\SaveAttributeOptionTranslationAction;
use App\Actions\Translations\SaveAttributeSectionTranslationAction;
use App\Actions\Translations\SaveAttributeTranslationAction;
use App\Actions\Translations\SaveCategoryTranslationAction;
use App\Actions\Translations\SaveProductTranslationAction;
use App\Actions\Translations\SaveUnitTranslationAction;
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
    public function editProduct(CentralProduct $product, Locale $locale): View
    {
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
        $action->handle($product, $locale, $request->only(['name', 'subtitle', 'short_description', 'description', 'seo_title', 'seo_description', 'status']));

        return back()->with('status', 'Translation saved.');
    }

    public function editCategory(CentralCategory $category, Locale $locale): View
    {
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
        $action->handle($category, $locale, $request->only(['name', 'description', 'seo_title', 'seo_description', 'status']));

        return back()->with('status', 'Translation saved.');
    }

    public function editAttribute(AttributeDefinition $attribute, Locale $locale): View
    {
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
        $action->handle($attribute, $locale, $request->only(['label', 'short_label', 'help_text', 'status']));

        return back()->with('status', 'Translation saved.');
    }

    public function editSection(AttributeSection $section, Locale $locale): View
    {
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
        $action->handle($section, $locale, $request->only(['name', 'description', 'status']));

        return back()->with('status', 'Translation saved.');
    }

    public function editOption(AttributeOption $option, Locale $locale): View
    {
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
        $action->handle($option, $locale, $request->only(['label', 'description', 'status']));

        return back()->with('status', 'Translation saved.');
    }

    public function editUnit(MeasurementUnit $unit, Locale $locale): View
    {
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
        $action->handle($unit, $locale, $request->only(['short_name', 'long_name', 'plural_name', 'symbol_position', 'space_between_value_and_unit', 'status']));

        return back()->with('status', 'Translation saved.');
    }
}
