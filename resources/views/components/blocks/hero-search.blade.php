@props(['config' => []])

<section data-theme-block="hero_search">
    <h2>{{ $config['title'] ?? 'Find the right product' }}</h2>
    @if (! empty($config['subtitle']))
        <p>{{ $config['subtitle'] }}</p>
    @endif
    <form role="search">
        <input
            type="search"
            disabled
            aria-label="Catalog search preview"
            placeholder="{{ $config['search_placeholder'] ?? 'Search products...' }}"
        >
    </form>
    <p>Search data will be available when the public catalog runtime is introduced.</p>
</section>
