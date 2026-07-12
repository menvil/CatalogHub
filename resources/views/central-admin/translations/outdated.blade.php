@extends('layouts.central-admin', ['activeNav' => 'Translations', 'pageTitle' => 'Outdated Translations'])

@section('content')
    <x-admin.card>
        <h2 class="mb-4 text-lg font-semibold">Outdated Translations</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-admin-muted">
                    <tr>
                        <th class="py-2 pr-4">Entity</th>
                        <th class="py-2 pr-4">Source</th>
                        <th class="py-2 pr-4">Translation</th>
                        <th class="py-2 pr-4">Locale</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2 pr-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-t border-admin-border">
                            <td class="py-2 pr-4">{{ $item['entity_type'] }}</td>
                            <td class="py-2 pr-4">{{ $item['source_label'] }}</td>
                            <td class="py-2 pr-4">{{ $item['translated_label'] }}</td>
                            <td class="py-2 pr-4">{{ $item['locale'] }}</td>
                            <td class="py-2 pr-4">{{ $item['status'] }}</td>
                            <td class="py-2 pr-4"><a class="text-admin-link" href="{{ $item['editor_url'] }}">Edit</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-4 text-admin-muted" colspan="6">No outdated translations.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
@endsection
