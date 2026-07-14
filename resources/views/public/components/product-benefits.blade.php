@php
    $benefitItems = is_array($benefits ?? null)
        ? array_values(array_filter($benefits, fn ($benefit) => (is_string($benefit) && trim($benefit) !== '') || (is_array($benefit) && (filled($benefit['title'] ?? null) || filled($benefit['description'] ?? null)))))
        : [];
@endphp
@if ($benefitItems !== [])
    <section data-product-benefits class="mt-10 rounded-3xl bg-blue-50 p-6 sm:p-8" aria-labelledby="product-benefits-title">
        <h2 id="product-benefits-title" class="text-2xl font-bold tracking-tight">Why it stands out</h2>
        <ul class="mt-5 grid gap-4 sm:grid-cols-2">
            @foreach ($benefitItems as $benefit)
                <li class="flex gap-3 rounded-2xl bg-white p-4 text-slate-700 shadow-sm">
                    <span aria-hidden="true" class="font-bold text-blue-600">✓</span>
                    <div>
                        @if (is_string($benefit))
                            <p class="font-medium">{{ $benefit }}</p>
                        @else
                            @if (filled($benefit['title'] ?? null))
                                <p class="font-semibold text-slate-950">{{ $benefit['title'] }}</p>
                            @endif
                            @if (filled($benefit['description'] ?? null))
                                <p @class(['mt-1' => filled($benefit['title'] ?? null), 'text-sm text-slate-600'])>{{ $benefit['description'] }}</p>
                            @endif
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif
