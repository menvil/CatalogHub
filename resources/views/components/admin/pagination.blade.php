@props(['previousUrl' => null, 'nextUrl' => null, 'page' => 1])

<nav {{ $attributes->class('flex items-center justify-between gap-admin-field') }} aria-label="Table pagination">
    <x-ui.button variant="secondary" :href="$previousUrl" :disabled="$previousUrl === null">Previous</x-ui.button>
    <span class="text-sm text-admin-muted">Page {{ $page }}</span>
    <x-ui.button variant="secondary" :href="$nextUrl" :disabled="$nextUrl === null">Next</x-ui.button>
</nav>
