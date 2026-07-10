<x-filament-panels::page>
    @php
        $product = $this->getProduct();
    @endphp

    <div class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Product</p>
                    <h2 class="text-2xl font-semibold tracking-normal text-gray-950 dark:text-white">{{ $product->name }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $product->model ?: 'No model' }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        {{ $product->status->value }}
                    </span>
                    @if ($product->category)
                        <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            {{ $product->category->name }}
                        </span>
                    @endif
                </div>
            </div>
        </section>

        @if (! $product->category)
            <section class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Choose a category first</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Product specs are driven by the category schema.</p>
            </section>
        @elseif ($product->category->attributeSections->isEmpty())
            <section class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">No attributes configured</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Add sections and attributes to the category schema before editing product specs.</p>
            </section>
        @else
            <section class="space-y-4">
                @foreach ($product->category->attributeSections as $section)
                    <article class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <header class="flex flex-col gap-2 border-b border-gray-200 p-5 dark:border-gray-800 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold tracking-normal text-gray-950 dark:text-white">{{ $section->name }}</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $section->code }}</p>
                            </div>

                            <span class="w-fit rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {{ $section->attributes->count() }} attributes
                            </span>
                        </header>

                        @if ($section->attributes->isEmpty())
                            <div class="p-5 text-sm text-gray-500 dark:text-gray-400">No attributes in this section yet.</div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                                    <thead class="bg-gray-50 dark:bg-gray-950">
                                        <tr>
                                            <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Attribute</th>
                                            <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Code</th>
                                            <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Type</th>
                                            <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                        @foreach ($section->attributes as $attribute)
                                            @php
                                                $existingValue = $product->attributeValues->firstWhere('attribute_definition_id', $attribute->id);
                                            @endphp
                                            <tr>
                                                <td class="px-5 py-3 font-medium text-gray-950 dark:text-white">{{ $attribute->name }}</td>
                                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $attribute->code }}</td>
                                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $attribute->data_type->value }}</td>
                                                <td class="px-5 py-3">
                                                    @if (in_array($attribute->data_type->value, ['integer', 'decimal'], true))
                                                        <input
                                                            type="number"
                                                            @if ($attribute->data_type->value === 'integer') step="1" @else step="any" @endif
                                                            wire:model.live="values.{{ $attribute->id }}.value_number"
                                                            class="w-40 rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                        >
                                                    @elseif ($attribute->data_type->value === 'string')
                                                        <input
                                                            type="text"
                                                            wire:model.live="values.{{ $attribute->id }}.value_text"
                                                            class="w-72 rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                        >
                                                    @elseif ($attribute->data_type->value === 'text')
                                                        <textarea
                                                            wire:model.live="values.{{ $attribute->id }}.value_text"
                                                            rows="2"
                                                            class="w-80 rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                        ></textarea>
                                                    @elseif ($attribute->data_type->value === 'boolean')
                                                        <select
                                                            wire:model.live="values.{{ $attribute->id }}.value_bool"
                                                            class="w-40 rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                        >
                                                            <option value="">Unknown</option>
                                                            <option value="1">Yes</option>
                                                            <option value="0">No</option>
                                                        </select>
                                                    @else
                                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $existingValue?->raw_value ?: 'No value yet' }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-filament-panels::page>
