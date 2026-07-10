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
            @php
                $missingRequiredAttributes = $this->missingRequiredAttributes();
            @endphp
            @if ($missingRequiredAttributes !== [])
                <section class="rounded-lg border border-warning-200 bg-warning-50 p-5 dark:border-warning-800 dark:bg-warning-950/20">
                    <h3 class="text-base font-semibold text-warning-900 dark:text-warning-100">Missing required attributes</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($missingRequiredAttributes as $missingAttribute)
                            <span class="rounded-md bg-white px-2 py-1 text-xs font-medium text-warning-800 ring-1 ring-warning-200 dark:bg-warning-950 dark:text-warning-100 dark:ring-warning-800">
                                {{ $missingAttribute->code }}
                            </span>
                        @endforeach
                    </div>
                </section>
            @endif

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
                                                    <div class="space-y-3">
                                                        @if (in_array($attribute->data_type->value, ['integer', 'decimal'], true))
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <input
                                                                    type="number"
                                                                    @if ($attribute->data_type->value === 'integer') step="1" @else step="any" @endif
                                                                    wire:model.live="values.{{ $attribute->id }}.value_number"
                                                                    class="w-40 rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                                >

                                                                @php
                                                                    $unitOptions = $this->unitOptionsFor($attribute);
                                                                @endphp
                                                                @if ($unitOptions !== [])
                                                                    <select
                                                                        wire:model.live="values.{{ $attribute->id }}.source_unit"
                                                                        class="w-48 rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                                    >
                                                                        <option value="">Source unit</option>
                                                                        @foreach ($unitOptions as $unitCode => $unitLabel)
                                                                            <option value="{{ $unitCode }}">{{ $unitLabel }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                @endif
                                                            </div>
                                                            @php
                                                                $canonicalPreview = $this->canonicalPreviewFor($attribute);
                                                            @endphp
                                                            @if ($canonicalPreview)
                                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                    <span class="font-medium text-gray-700 dark:text-gray-200">
                                                                        @if (filled($values[$attribute->id]['source_unit'] ?? null) && filled($canonicalPreview['unit']))
                                                                            Conversion:
                                                                        @else
                                                                            Canonical:
                                                                        @endif
                                                                    </span>
                                                                    {{ $canonicalPreview['label'] }}
                                                                    @if ($canonicalPreview['warning'])
                                                                        <span class="text-warning-700 dark:text-warning-300">{{ $canonicalPreview['warning'] }}</span>
                                                                    @endif
                                                                </div>
                                                            @endif
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
                                                        @elseif ($attribute->data_type->value === 'enum')
                                                            @if ($attribute->options->isEmpty())
                                                                <span class="text-sm text-warning-700 dark:text-warning-300">No options configured for this enum attribute.</span>
                                                            @else
                                                                <select
                                                                    wire:model.live="values.{{ $attribute->id }}.value_enum_code"
                                                                    class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                                >
                                                                    <option value="">No option</option>
                                                                    @foreach ($attribute->options as $option)
                                                                        <option value="{{ $option->code }}">{{ $option->label ?: $option->code }}</option>
                                                                    @endforeach
                                                                </select>
                                                            @endif
                                                        @elseif ($attribute->data_type->value === 'multi_enum')
                                                            @if ($attribute->options->isEmpty())
                                                                <span class="text-sm text-warning-700 dark:text-warning-300">No options configured for this multi-enum attribute.</span>
                                                            @else
                                                                <div class="flex flex-wrap gap-3">
                                                                    @foreach ($attribute->options as $option)
                                                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                                                            <input
                                                                                type="checkbox"
                                                                                value="{{ $option->code }}"
                                                                                wire:model.live="values.{{ $attribute->id }}.value_json"
                                                                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950"
                                                                            >
                                                                            <span>{{ $option->label ?: $option->code }}</span>
                                                                        </label>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        @else
                                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $existingValue?->raw_value ?: 'No value yet' }}</span>
                                                        @endif

                                                        <label class="block">
                                                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Raw value</span>
                                                            <input
                                                                type="text"
                                                                wire:model.live="values.{{ $attribute->id }}.raw_value"
                                                                class="mt-1 w-72 rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                            >
                                                        </label>

                                                        <label class="block">
                                                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Confidence</span>
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                max="1"
                                                                step="0.01"
                                                                wire:model.live="values.{{ $attribute->id }}.confidence"
                                                                class="mt-1 w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                            >
                                                        </label>

                                                        <div class="grid gap-3 md:grid-cols-2">
                                                            <label class="block">
                                                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Source type</span>
                                                                <select
                                                                    wire:model.live="values.{{ $attribute->id }}.source_type"
                                                                    class="mt-1 w-44 rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                                >
                                                                    <option value="">Unknown</option>
                                                                    <option value="manual">Manual</option>
                                                                    <option value="manufacturer">Manufacturer</option>
                                                                    <option value="merchant">Merchant</option>
                                                                    <option value="import">Import</option>
                                                                </select>
                                                            </label>

                                                            <label class="block">
                                                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Source note</span>
                                                                <textarea
                                                                    rows="2"
                                                                    wire:model.live="values.{{ $attribute->id }}.source_reference.note"
                                                                    class="mt-1 w-72 rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                                ></textarea>
                                                            </label>
                                                        </div>
                                                    </div>
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

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold tracking-normal text-gray-950 dark:text-white">Grouped Specs Preview</h3>

                <div class="mt-4 space-y-4">
                    @forelse ($this->groupedSpecsPreview() as $sectionPreview)
                        <div class="border-t border-gray-200 pt-4 first:border-t-0 first:pt-0 dark:border-gray-800">
                            <h4 class="font-semibold text-gray-950 dark:text-white">{{ $sectionPreview['section'] }}</h4>

                            @if ($sectionPreview['attributes'] === [])
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No values yet.</p>
                            @else
                                <dl class="mt-3 grid gap-2 md:grid-cols-2">
                                    @foreach ($sectionPreview['attributes'] as $attributePreview)
                                        <div class="grid grid-cols-2 gap-3 rounded-md border border-gray-200 p-3 text-sm dark:border-gray-800">
                                            <dt class="font-medium text-gray-950 dark:text-white">{{ $attributePreview['name'] }}</dt>
                                            <dd class="text-gray-600 dark:text-gray-300">{{ $attributePreview['value'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No preview available.</p>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
