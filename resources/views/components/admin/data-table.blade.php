@php
    $alignmentClasses = [
        'start' => 'text-left',
        'center' => 'text-center',
        'end' => 'text-right',
    ];
    $columnCount = count($columns) + ($selectable ? 1 : 0);
@endphp

<div {{ $attributes->class('overflow-x-auto rounded-admin-card border border-admin-border bg-admin-surface') }} data-admin-data-table>
    <table @if ($tableId) id="{{ $tableId }}" @endif class="min-w-foundation-table w-full border-collapse text-sm">
        <caption class="sr-only">{{ $caption }}</caption>
        <thead class="bg-admin-surface-muted text-admin-muted">
            <tr>
                @if ($selectable)
                    <th scope="col" class="w-12 px-3 py-2 text-left">
                        <input type="checkbox" data-admin-select-visible aria-label="Select all visible rows">
                    </th>
                @endif
                @foreach ($columns as $column)
                    <th scope="col" class="px-3 py-2 font-semibold {{ $alignmentClasses[$column['align'] ?? 'start'] }}">
                        {{ $column['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-admin-border">
            @forelse ($rows as $row)
                @php($identifier = (string) $row[$rowKey])
                <tr data-row-id="{{ $identifier }}">
                    @if ($selectable)
                        <td class="w-12 px-3 py-2">
                            <input type="checkbox" value="{{ $identifier }}" data-admin-row-select aria-label="Select row {{ $identifier }}">
                        </td>
                    @endif
                    @foreach ($columns as $column)
                        <td class="px-3 py-2 text-admin-text {{ $alignmentClasses[$column['align'] ?? 'start'] }}">
                            {{ data_get($row, $column['key']) }}
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
