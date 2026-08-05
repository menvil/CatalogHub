@props(['items' => []])

@if ($items !== [])
    <nav aria-label="Breadcrumbs">
        <ol class="flex flex-wrap items-center gap-2 text-sm text-admin-muted">
            @foreach ($items as $item)
                <li class="flex items-center gap-2">
                    @if (! $loop->first)
                        <span aria-hidden="true">/</span>
                    @endif

                    @if (! $loop->last && filled($item['url'] ?? null))
                        <a
                            href="{{ $item['url'] }}"
                            class="font-medium text-admin-muted hover:text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
                        >
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
