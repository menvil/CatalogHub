@props([
    'title',
    'message',
    'confirmLabel' => 'Confirm',
    'cancelLabel' => 'Cancel',
    'variant' => 'default',
    'open' => true,
    'contained' => false,
])

@php
    $variantClasses = [
        'default' => [
            'icon' => 'bg-admin-primary-soft text-admin-primary ring-admin-primary/20',
            'confirm' => 'bg-admin-primary text-white hover:bg-blue-700',
        ],
        'warning' => [
            'icon' => 'bg-admin-warning-soft text-admin-warning ring-admin-warning/25',
            'confirm' => 'bg-admin-warning text-white hover:bg-amber-700',
        ],
        'danger' => [
            'icon' => 'bg-admin-danger-soft text-admin-danger ring-admin-danger/25',
            'confirm' => 'bg-admin-danger text-white hover:bg-red-800',
        ],
    ];

    $classes = $variantClasses[$variant] ?? $variantClasses['default'];
@endphp

<div
    {{ $attributes->class([
        'inset-0 z-50 flex items-center justify-center p-admin-page',
        'absolute' => $contained,
        'fixed' => ! $contained,
        'hidden' => ! $open,
    ]) }}
    data-admin-modal
    data-admin-modal-open="{{ $open ? 'true' : 'false' }}"
>
    <button
        type="button"
        class="absolute inset-0 bg-admin-text/35"
        data-admin-modal-close
        aria-label="Close confirmation modal backdrop"
    ></button>

    <section
        class="relative w-full max-w-lg rounded-admin-modal border border-admin-border bg-admin-surface shadow-admin-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="admin-confirmation-modal-title"
        aria-describedby="admin-confirmation-modal-message"
    >
        <div class="p-6">
            <div class="flex gap-admin-card">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-admin-badge ring-1 ring-inset {{ $classes['icon'] }}" aria-hidden="true">
                    !
                </div>

                <div class="min-w-0 flex-1">
                    <h2 id="admin-confirmation-modal-title" class="text-lg font-semibold text-admin-text">{{ $title }}</h2>
                    <p id="admin-confirmation-modal-message" class="mt-2 text-sm text-admin-muted">{{ $message }}</p>

                    @if (trim($slot) !== '')
                        <div class="mt-4 text-sm text-admin-text">
                            {{ $slot }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <footer class="flex flex-col-reverse gap-admin-field border-t border-admin-border bg-admin-surface-muted p-admin-card sm:flex-row sm:justify-end">
            <button
                type="button"
                class="rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm font-medium text-admin-muted focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
                data-admin-modal-close
            >
                {{ $cancelLabel }}
            </button>

            <button
                type="button"
                class="rounded-admin-input px-3 py-2 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary {{ $classes['confirm'] }}"
            >
                {{ $confirmLabel }}
            </button>
        </footer>
    </section>
</div>
