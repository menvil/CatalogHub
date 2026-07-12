@extends('layouts.central-admin', ['activeNav' => 'Translations', 'pageTitle' => $title])

@section('content')
    <x-admin.card>
        <div class="mb-4">
            <h2 class="text-lg font-semibold">{{ $title }}</h2>
            <p class="text-sm text-admin-muted">Source: {{ $sourceLabel }} · Locale: {{ $locale->code }}</p>
            @isset($preview)
                <p class="mt-1 text-sm text-admin-muted">Example: {{ $preview }}</p>
            @endisset
        </div>

        <form method="POST" action="{{ $saveRoute }}" class="space-y-4">
            @csrf
            @foreach ($fields as $field)
                <label class="block">
                    <span class="text-sm font-medium">{{ str($field)->replace('_', ' ')->headline() }}</span>
                    @if ($field === 'space_between_value_and_unit')
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input class="mt-1" type="checkbox" name="{{ $field }}" value="1" @checked((bool) old($field, $translation?->{$field} ?? true))>
                    @elseif (str_contains($field, 'description') || $field === 'help_text')
                        <textarea class="mt-1 w-full rounded border-admin-border" name="{{ $field }}" rows="4">{{ old($field, $translation?->{$field}) }}</textarea>
                    @else
                        <input class="mt-1 w-full rounded border-admin-border" type="text" name="{{ $field }}" value="{{ old($field, $translation?->{$field}) }}">
                    @endif
                </label>
            @endforeach

            <label class="block">
                <span class="text-sm font-medium">Status</span>
                <select class="mt-1 w-full rounded border-admin-border" name="status">
                    @foreach (\App\Enums\TranslationStatus::options() as $value => $label)
                        @continue($value === \App\Enums\TranslationStatus::Approved->value)
                        <option value="{{ $value }}" @selected(old('status', $translation?->status?->value ?? 'human_reviewed') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            @if ($errors->any())
                <div class="rounded-admin-input border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <button class="rounded bg-amber-600 px-4 py-2 text-white" type="submit">Save translation</button>
        </form>
    </x-admin.card>
@endsection
