@props([
    'title' => null,
    'description' => null,
    'padding' => 'md',
    'variant' => 'default',
])

@php
    $paddingClasses = [
        'sm' => 'p-3',
        'md' => 'p-admin-card',
        'lg' => 'p-6',
    ];

    $variantClasses = [
        'default' => 'border-admin-border bg-admin-surface shadow-admin-card',
        'subtle' => 'border-admin-border bg-admin-surface-muted',
        'success' => 'border-admin-success/30 bg-admin-success-soft',
        'danger' => 'border-admin-danger/30 bg-admin-danger-soft',
    ];

    $paddingClass = $paddingClasses[$padding] ?? $paddingClasses['md'];
    $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
@endphp

<section
    {{ $attributes->class([
        'rounded-admin-card border text-admin-text',
        $variantClass,
    ]) }}
    data-admin-card="{{ $variant }}"
>
    @if ($title || $description || isset($header) || isset($actions))
        <header class="flex flex-col gap-admin-field border-b border-admin-border {{ $paddingClass }} md:flex-row md:items-start md:justify-between">
            <div class="min-w-0">
                @isset($header)
                    {{ $header }}
                @else
                    @if ($title)
                        <h2 class="text-base font-semibold text-admin-text">{{ $title }}</h2>
                    @endif

                    @if ($description)
                        <p class="mt-1 text-sm text-admin-muted">{{ $description }}</p>
                    @endif
                @endisset
            </div>

            @isset($actions)
                <div class="flex flex-wrap items-center gap-admin-field">
                    {{ $actions }}
                </div>
            @endisset
        </header>
    @endif

    <div class="{{ $paddingClass }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="border-t border-admin-border {{ $paddingClass }}">
            {{ $footer }}
        </footer>
    @endisset
</section>
