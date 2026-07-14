@php
    $seoData = is_array($seo ?? null) ? $seo : [];
    $metaTitle = $seoData['meta_title'] ?? $seoData['title'] ?? $fallbackTitle ?? config('app.name', 'CatalogHub');
    $metaDescription = $seoData['meta_description'] ?? $seoData['description'] ?? null;
    $canonicalUrl = $seoData['canonical_url'] ?? $seoData['canonical'] ?? null;
    $ogTitle = $seoData['og_title'] ?? $metaTitle;
    $ogDescription = $seoData['og_description'] ?? $metaDescription;
    $ogImage = $seoData['og_image'] ?? null;
@endphp
<title>{{ $metaTitle }}</title>
@if (filled($metaDescription))
    <meta name="description" content="{{ $metaDescription }}">
@endif
@if (filled($canonicalUrl))
    <link rel="canonical" href="{{ $canonicalUrl }}">
@endif
@if (filled($seoData['robots'] ?? null))
    <meta name="robots" content="{{ $seoData['robots'] }}">
@endif
@if (filled($ogTitle))
    <meta property="og:title" content="{{ $ogTitle }}">
@endif
@if (filled($ogDescription))
    <meta property="og:description" content="{{ $ogDescription }}">
@endif
@if (filled($ogImage))
    <meta property="og:image" content="{{ $ogImage }}">
@endif
<meta property="og:type" content="website">
