@php
    $brandTabs = [];
    if (auth()->user()?->can('catalog.products.manage') === true) {
        $brandTabs[] = ['key' => 'overview', 'label' => 'Overview', 'url' => route('central.brands.show', $brand, absolute: false)];
        $brandTabs[] = ['key' => 'media', 'label' => 'Media', 'url' => route('central.brands.media', $brand, absolute: false)];
    }
    if (auth()->user()?->can('translations.manage') === true) {
        $brandTabs[] = ['key' => 'translations', 'label' => 'Translations', 'url' => route('central.brands.translations.index', $brand, absolute: false)];
    }
@endphp

<x-admin.tabs :items="$brandTabs" :active="$active" aria-label="Brand sections" />
