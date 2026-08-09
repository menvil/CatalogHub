@props([
    'id',
    'action' => null,
    'method' => 'post',
    'leaveWarning' => true,
])

@php
    $normalizedMethod = strtolower((string) $method);
    throw_unless(in_array($normalizedMethod, ['get', 'post', 'put', 'patch', 'delete'], true), \InvalidArgumentException::class, "Unsupported form method [{$method}].");
    $nativeMethod = $normalizedMethod === 'get' ? 'GET' : 'POST';
@endphp

<form
    id="{{ $id }}"
    @if ($action !== null) action="{{ $action }}" @endif
    method="{{ $nativeMethod }}"
    {{ $attributes }}
    data-admin-form-state
    data-admin-form-dirty="false"
    data-admin-form-submitting="false"
    data-admin-form-changed-while-submitting="false"
    data-admin-form-leave-warning="{{ $leaveWarning ? 'true' : 'false' }}"
>
    @if ($nativeMethod === 'POST')
        @csrf
        @if ($normalizedMethod !== 'post') @method(strtoupper($normalizedMethod)) @endif
    @endif
    {{ $slot }}
</form>
