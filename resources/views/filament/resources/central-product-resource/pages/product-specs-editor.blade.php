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
        @else
            <section class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Product Specs</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Attribute editors will appear here as the category schema is loaded.</p>
            </section>
        @endif
    </div>
</x-filament-panels::page>
