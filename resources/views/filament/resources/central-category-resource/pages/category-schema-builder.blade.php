<x-filament-panels::page>
    @php
        $category = $this->getCategory();
    @endphp

    <div class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</p>
                    <h2 class="text-2xl font-semibold tracking-normal text-gray-950 dark:text-white">{{ $category->name }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $category->slug }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        {{ $category->status->value }}
                    </span>
                    <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        {{ $category->attributeSections->count() }} sections
                    </span>
                </div>
            </div>
        </section>

        @if ($category->attributeSections->isEmpty())
            <section class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">No attribute sections yet</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Schema structure will appear here after sections and attributes are added.</p>
            </section>
        @else
            <section class="space-y-4">
                @foreach ($category->attributeSections as $section)
                    <article class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <header class="flex flex-col gap-2 border-b border-gray-200 p-5 dark:border-gray-800 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold tracking-normal text-gray-950 dark:text-white">{{ $section->name }}</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $section->code }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                    position {{ $section->position }}
                                </span>
                                <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                    {{ $section->display_style }}
                                </span>
                            </div>
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
                                            <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Flags</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                        @foreach ($section->attributes as $attribute)
                                            <tr>
                                                <td class="px-5 py-3 font-medium text-gray-950 dark:text-white">{{ $attribute->name }}</td>
                                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $attribute->code }}</td>
                                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $attribute->data_type->value }}</td>
                                                <td class="px-5 py-3">
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach ([
                                                            'required' => $attribute->is_required,
                                                            'filterable' => $attribute->is_filterable,
                                                            'sortable' => $attribute->is_sortable,
                                                            'comparable' => $attribute->is_comparable,
                                                            'visible' => $attribute->is_visible,
                                                            'searchable' => $attribute->is_searchable,
                                                        ] as $label => $enabled)
                                                            @if ($enabled)
                                                                <span class="rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">{{ $label }}</span>
                                                            @endif
                                                        @endforeach
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
        @endif

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4">
                <h3 class="text-lg font-semibold tracking-normal text-gray-950 dark:text-white">Product schema preview</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Future product specs layout grouped by section.</p>
            </div>

            @forelse ($this->getSchemaPreview() as $sectionPreview)
                <div class="border-t border-gray-200 py-4 first:border-t-0 first:pt-0 last:pb-0 dark:border-gray-800">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <h4 class="font-semibold text-gray-950 dark:text-white">{{ $sectionPreview['section'] }}</h4>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($sectionPreview['attributes']) }} attributes</span>
                    </div>

                    @if (empty($sectionPreview['attributes']))
                        <p class="mt-2 text-sm text-warning-700 dark:text-warning-300">Empty section</p>
                    @else
                        <div class="mt-3 grid gap-2 md:grid-cols-2">
                            @foreach ($sectionPreview['attributes'] as $attributePreview)
                                <div class="rounded-md border border-gray-200 p-3 dark:border-gray-800">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-medium text-gray-950 dark:text-white">{{ $attributePreview['name'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $attributePreview['code'] }} · {{ $attributePreview['data_type'] }}</p>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $attributePreview['options_count'] }} options</span>
                                    </div>

                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach ($attributePreview['flags'] as $label => $enabled)
                                            @if ($enabled)
                                                <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ $label }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No preview available until sections are added.</p>
            @endforelse
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4">
                <h3 class="text-lg font-semibold tracking-normal text-gray-950 dark:text-white">Schema issues</h3>
            </div>

            @forelse ($this->getSchemaIssues() as $issue)
                <div class="flex flex-col gap-1 border-t border-gray-200 py-3 first:border-t-0 first:pt-0 last:pb-0 dark:border-gray-800 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-medium text-gray-950 dark:text-white">{{ $issue->message }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $issue->code }}</p>
                    </div>
                    <span class="w-fit rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ $issue->severity }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No schema issues detected.</p>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
