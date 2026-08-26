@props([
    'id', 'name' => 'tags', 'label' => 'Tags', 'values' => [], 'help' => null, 'error' => null,
    'disabled' => false, 'max' => 20, 'form' => null, 'resetValues' => null,
])
@php
    throw_if(trim((string) $id) === '', \InvalidArgumentException::class, 'Tag input IDs cannot be empty.');
    $filterTagValues = static fn (mixed $items): array => array_values(array_filter(
        is_array($items) ? $items : [],
        static fn (mixed $value): bool => is_string($value),
    ));
    $tagValues = $filterTagValues($values);
    $resetTagValues = $resetValues === null ? $tagValues : $filterTagValues($resetValues);
    $inputName = str_ends_with((string) $name, '[]') ? (string) $name : $name.'[]';
    $describedBy = collect([
        filled($help) ? "{$id}-help" : null,
        filled($error) ? "{$id}-error" : null,
        "{$id}-client-error",
    ])->filter()->implode(' ');
@endphp

<x-ui.form.field :id="$id" :control-id="$id.'-input'" :label="$label" :help="$help" :error="$error">
    <div
        {{ $attributes->class(['rounded-admin-input border bg-admin-surface p-2', 'border-admin-danger' => filled($error), 'border-admin-border' => blank($error), 'opacity-60' => $disabled]) }}
        data-ui-tag-input
        data-ui-tag-input-name="{{ $inputName }}"
        data-ui-tag-input-max="{{ (int) $max }}"
        data-ui-tag-input-disabled="{{ $disabled ? 'true' : 'false' }}"
        @if (filled($form)) data-ui-tag-input-form="{{ $form }}" @endif
    >
        <div class="flex min-h-8 flex-wrap gap-2" data-ui-tag-input-chips aria-live="polite">
            @foreach ($tagValues as $tag)
                <span class="inline-flex max-w-full items-center gap-1 rounded-admin-badge bg-admin-surface-muted px-2.5 py-1 text-sm font-medium text-admin-text ring-1 ring-inset ring-admin-border" data-ui-tag-input-chip data-tag-name="{{ $tag }}">
                    <span class="truncate">{{ $tag }}</span>
                    <input type="hidden" name="{{ $inputName }}" value="{{ $tag }}" @if (filled($form)) form="{{ $form }}" @endif @disabled($disabled)>
                    <button type="button" class="rounded px-1 text-admin-muted hover:text-admin-danger focus-visible:outline focus-visible:outline-2 focus-visible:outline-admin-primary" aria-label="Remove {{ $tag }}" data-ui-tag-input-remove @disabled($disabled)>×</button>
                </span>
            @endforeach
        </div>
        <template data-ui-tag-input-reset-values>
            @foreach ($resetTagValues as $tag)
                <span data-ui-tag-input-reset-value data-tag-name="{{ $tag }}"></span>
            @endforeach
        </template>
        <div class="mt-2 flex min-w-0 flex-col gap-2 sm:flex-row">
            <input
                id="{{ $id }}-input"
                type="text"
                maxlength="80"
                autocomplete="off"
                class="min-h-10 min-w-0 flex-1 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text focus:border-admin-primary focus:outline-none focus:ring-2 focus:ring-admin-primary/20 disabled:cursor-not-allowed"
                placeholder="Type a tag"
                aria-describedby="{{ $describedBy }}"
                @if (filled($error)) aria-invalid="true" @endif
                @disabled($disabled)
                data-ui-tag-input-text
            >
            <x-ui.button variant="secondary" data-ui-tag-input-add :disabled="$disabled">Add tag</x-ui.button>
        </div>
        <p id="{{ $id }}-client-error" class="mt-1.5 hidden text-xs font-medium text-admin-danger" role="alert" data-ui-tag-input-error></p>
    </div>
</x-ui.form.field>
