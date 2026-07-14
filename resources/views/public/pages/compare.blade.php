@extends('public.layouts.app')

@section('title', 'Compare products')

@section('content')
    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Product comparison</p>
    <h1 class="mt-2 text-4xl font-bold tracking-tight">Compare products</h1>

    @if ($comparison['error'])
        <div class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
            <p class="font-semibold">{{ $comparison['error'] }}</p>
            <p class="mt-2 text-sm">Choose between two and four projected products from one category.</p>
        </div>
    @else
        <div class="mt-8 overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <table data-comparison-table class="min-w-full border-collapse text-left text-sm">
                <thead class="bg-slate-950 text-white">
                    <tr>
                        <th class="min-w-48 px-5 py-4">Specification</th>
                        @foreach ($comparison['products'] as $product)
                            <th class="min-w-56 px-5 py-4 text-base">{{ $product['title'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($comparison['sections'] as $section)
                        <tr class="bg-slate-100">
                            <th colspan="{{ count($comparison['products']) + 1 }}" class="px-5 py-3 font-bold">{{ $section['label'] }}</th>
                        </tr>
                        @foreach ($section['attributes'] as $attribute)
                            <tr>
                                <th class="px-5 py-4 font-medium text-slate-600">{{ $attribute['label'] }}</th>
                                @foreach ($attribute['values'] as $value)
                                    <td class="px-5 py-4 font-semibold text-slate-950">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
