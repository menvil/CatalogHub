@if (! in_array('q', $excludedFilters, true) && request('q'))
    <input type="hidden" name="q" value="{{ request('q') }}">
@endif
@if (! in_array('status', $excludedFilters, true) && request('status'))
    <input type="hidden" name="status" value="{{ request('status') }}">
@endif
@if (! in_array('sort', $excludedFilters, true) && request('sort'))
    <input type="hidden" name="sort" value="{{ request('sort') }}">
    <input type="hidden" name="direction" value="{{ request('direction', 'asc') }}">
@endif
