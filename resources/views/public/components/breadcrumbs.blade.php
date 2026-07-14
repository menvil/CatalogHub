@php
    $breadcrumbItems = is_array($items ?? null)
        ? array_values(array_filter($items, fn ($item) => is_array($item) && filled($item['label'] ?? null)))
        : [];
@endphp
@if ($breadcrumbItems !== [])
    <nav data-public-breadcrumbs aria-label="Breadcrumb" class="overflow-x-auto text-sm text-slate-500">
        <ol class="flex min-w-max items-center gap-2">
            @foreach ($breadcrumbItems as $item)
                <li class="flex items-center gap-2">
                    @if (! $loop->last && filled($item['url'] ?? null))
                        <a href="{{ $item['url'] }}" class="rounded transition hover:text-slate-950">{{ $item['label'] }}</a>
                    @elseif ($loop->last)
                        <span aria-current="page">{{ $item['label'] }}</span>
                    @else
                        <span>{{ $item['label'] }}</span>
                    @endif
                    @unless ($loop->last)
                        <span aria-hidden="true" class="text-slate-300">/</span>
                    @endunless
                </li>
            @endforeach
        </ol>
    </nav>
@endif
