@foreach (['q', 'status', 'schema', 'level', 'locale', 'site', 'translation', 'sort', 'direction', 'per_page'] as $key)
    @if (! in_array($key, $excludedFilters, true) && request()->filled($key))
        <input type="hidden" name="{{ $key }}" value="{{ request($key) }}">
    @endif
@endforeach
