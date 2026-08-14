@php
    $alignmentClasses = [
        'start' => 'text-left',
        'center' => 'text-center',
        'end' => 'text-right',
    ];
    $responsiveClasses = [
        'always' => '',
        'sm' => 'hidden sm:table-cell',
        'md' => 'hidden md:table-cell',
        'lg' => 'hidden lg:table-cell',
    ];
    $columnCount = count($columns) + ($selectable ? 1 : 0);
@endphp

<div {{ $attributes->class('overflow-x-auto rounded-admin-card border border-admin-border bg-admin-surface') }} data-admin-data-table>
    <table
        @if ($tableId) id="{{ $tableId }}" @endif
        @class([
            'w-full border-collapse text-sm',
            'min-w-foundation-table' => ! $mobileCompact,
            'table-fixed sm:min-w-foundation-table sm:table-auto' => $mobileCompact,
        ])
    >
        <caption class="sr-only">{{ $caption }}</caption>
        <thead class="bg-admin-surface-muted text-admin-muted">
            <tr>
                @if ($selectable)
                    <th scope="col" class="w-12 px-3 py-2 text-left">
                        <input type="checkbox" data-admin-select-visible aria-label="Select all visible rows">
                    </th>
                @endif
                @foreach ($columns as $column)
                    @php
                        $responsiveClass = $responsiveClasses[$column['responsive'] ?? 'always'];
                    @endphp
                    <th scope="col" class="px-3 py-2 font-semibold {{ $alignmentClasses[$column['align'] ?? 'start'] }} {{ $responsiveClass }}">
                        @if (filled($column['sortUrl'] ?? null))
                            <a
                                href="{{ $column['sortUrl'] }}"
                                class="inline-flex items-center gap-1 text-admin-muted hover:text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
                                aria-label="Sort by {{ $column['label'] }} {{ ($column['sortDirection'] ?? null) === 'asc' ? 'descending' : 'ascending' }}"
                            >
                                {{ $column['label'] }}
                                @if (filled($column['sortDirection'] ?? null))
                                    <span aria-hidden="true">{{ $column['sortDirection'] === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        @else
                            {{ $column['label'] }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-admin-border">
            @forelse ($rows as $row)
                @php
                    $identifier = (string) $row[$rowKey];
                @endphp
                <tr data-row-id="{{ $identifier }}">
                    @if ($selectable)
                        <td class="w-12 px-3 py-2">
                            <input type="checkbox" value="{{ $identifier }}" data-admin-row-select aria-label="Select row {{ $identifier }}">
                        </td>
                    @endif
                    @foreach ($columns as $column)
                        @php
                            $responsiveClass = $responsiveClasses[$column['responsive'] ?? 'always'];
                            $type = $column['type'] ?? 'text';
                            $value = data_get($row, $column['key']);
                            $safeLink = $type === 'link'
                                && is_array($value)
                                && \App\Support\Presentation\SafePresentationUrl::allows($value['url'] ?? null);
                        @endphp
                        <td class="px-3 py-2 text-admin-text {{ $alignmentClasses[$column['align'] ?? 'start'] }} {{ $responsiveClass }}">
                            @if ($type === 'status')
                                <x-admin.status-badge :label="$value['label']" :variant="$value['variant']" size="sm" />
                            @elseif ($safeLink)
                                <a
                                    href="{{ $value['url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-block max-w-56 truncate align-bottom font-medium text-admin-primary hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
                                    aria-label="Visit {{ $value['label'] }} website (opens in a new tab)"
                                >{{ $value['label'] }}</a>
                            @elseif ($type === 'link' && is_array($value))
                                {{ $value['label'] ?? '—' }}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnCount }}" class="px-3 py-8 text-center text-admin-muted">{{ $empty }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
