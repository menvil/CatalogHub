@php
    $messages = collect([
        'success' => [
            'message' => session('success'),
            'classes' => 'border-admin-success bg-admin-success-soft text-admin-text',
        ],
        'warning' => [
            'message' => session('warning'),
            'classes' => 'border-admin-warning bg-admin-warning-soft text-admin-text',
        ],
        'error' => [
            'message' => session('error'),
            'classes' => 'border-admin-danger bg-admin-danger-soft text-admin-text',
        ],
    ])->filter(fn (array $notice): bool => filled($notice['message']));
@endphp

@if ($messages->isNotEmpty())
    <section
        class="space-y-admin-field"
        aria-label="Notifications"
        aria-live="polite"
        data-admin-flash-region
    >
        @foreach ($messages as $tone => $notice)
            <div
                class="rounded-admin-card border px-admin-card py-admin-field text-sm {{ $notice['classes'] }}"
                role="{{ $tone === 'error' ? 'alert' : 'status' }}"
                data-admin-flash="{{ $tone }}"
            >
                {{ $notice['message'] }}
            </div>
        @endforeach
    </section>
@endif
