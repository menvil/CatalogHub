@php
    $visibleSections = is_array($sections ?? null)
        ? array_values(array_filter($sections, fn ($section) => is_array($section) && ! empty($section['attributes']) && is_array($section['attributes'])))
        : [];
@endphp
@if ($visibleSections !== [])
    <section data-product-specs class="mt-12" aria-labelledby="product-specifications-title">
        <h2 id="product-specifications-title" class="text-2xl font-bold tracking-tight">Specifications</h2>

        <div class="mt-6 space-y-6">
            @foreach ($visibleSections as $section)
                <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <h3 class="border-b border-slate-200 bg-slate-50 px-5 py-4 text-lg font-semibold">{{ $section['label'] ?? $section['code'] ?? 'Details' }}</h3>
                    <dl class="divide-y divide-slate-100">
                        @foreach ($section['attributes'] as $attribute)
                            @if (is_array($attribute))
                                <div class="grid gap-1 px-5 py-4 sm:grid-cols-2 sm:gap-6">
                                    <dt class="text-sm font-medium text-slate-600">{{ $attribute['label'] ?? $attribute['code'] ?? 'Specification' }}</dt>
                                    <dd class="text-sm font-semibold text-slate-950">{{ $attribute['display_value'] ?? $attribute['value'] ?? '—' }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </div>
    </section>
@endif
