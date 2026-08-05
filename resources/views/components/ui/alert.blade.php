@props(['tone' => 'neutral', 'message', 'title' => null])

@php
    $tones = [
        'success' => 'border-admin-success bg-admin-success-soft',
        'warning' => 'border-admin-warning bg-admin-warning-soft',
        'danger' => 'border-admin-danger bg-admin-danger-soft',
        'info' => 'border-admin-info bg-admin-info-soft',
        'neutral' => 'border-admin-border bg-admin-surface-muted',
    ];
    throw_unless(isset($tones[$tone]), \InvalidArgumentException::class, "Unknown alert tone [{$tone}].");
@endphp

<aside {{ $attributes->class("rounded-admin-card border px-admin-card py-admin-field text-sm text-admin-text {$tones[$tone]}") }} role="{{ $tone === 'danger' ? 'alert' : 'status' }}" data-ui-alert="{{ $tone }}">
    @if ($title)<p class="font-semibold">{{ $title }}</p>@endif
    <p>{{ $message }}</p>
</aside>
