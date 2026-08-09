@props(['tone' => 'neutral', 'message', 'dismissible' => false])

<div {{ $attributes->class('flex items-start gap-admin-field rounded-admin-card shadow-admin-card') }} data-ui-toast>
    <div class="min-w-0 flex-1"><x-ui.alert :tone="$tone" :message="$message" /></div>
    @if ($dismissible)
        <button type="button" class="p-2 text-admin-muted" data-ui-feedback-dismiss aria-label="Dismiss notification">×</button>
    @endif
</div>
