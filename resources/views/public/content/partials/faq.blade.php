<div class="mt-10 space-y-4">
    @foreach (collect($items)->sortBy('position') as $item)
        <details class="rounded-2xl border border-slate-200 bg-white p-5" @if ($loop->first) open @endif>
            <summary class="cursor-pointer text-lg font-semibold text-slate-950">{{ $item['question'] }}</summary>
            <p class="mt-3 leading-7 text-slate-700">{{ $item['answer'] }}</p>
        </details>
    @endforeach
</div>
