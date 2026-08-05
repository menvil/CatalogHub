@props(['for', 'required' => false, 'optional' => false])

<label {{ $attributes->class('block text-sm font-medium text-admin-text') }} for="{{ $for }}">
    {{ $slot }}
    @if ($required)<span class="text-admin-danger" aria-hidden="true">*</span><span class="sr-only"> required</span>@endif
    @if ($optional)<span class="font-normal text-admin-muted">(optional)</span>@endif
</label>
