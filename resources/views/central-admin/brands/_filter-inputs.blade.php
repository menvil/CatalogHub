@if (! in_array('q', $excludedFilters, true) && request('q'))
    <input type="hidden" name="q" value="{{ request('q') }}">
@endif
@if (! in_array('status', $excludedFilters, true) && request('status'))
    <input type="hidden" name="status" value="{{ request('status') }}">
@endif
@foreach (['country', 'coverage', 'translation', 'quality'] as $filter)
    @if (! in_array($filter, $excludedFilters, true) && request($filter))
        <input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">
    @endif
@endforeach
@if (! in_array('sort', $excludedFilters, true) && request('sort'))
    <input type="hidden" name="sort" value="{{ request('sort') }}">
    <input type="hidden" name="direction" value="{{ request('direction', 'asc') }}">
@endif
