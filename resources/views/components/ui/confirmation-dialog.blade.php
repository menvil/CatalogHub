@props([
    'id',
    'title',
    'message',
    'confirmLabel' => 'Confirm',
    'cancelLabel' => 'Cancel',
    'destructive' => false,
    'open' => false,
    'contained' => false,
    'confirmForm' => null,
])

<x-admin.confirmation-modal
    :id="$id"
    :title="$title"
    :message="$message"
    :confirm-label="$confirmLabel"
    :cancel-label="$cancelLabel"
    :variant="$destructive ? 'danger' : 'default'"
    :open="$open"
    :contained="$contained"
    :confirm-form="$confirmForm"
    :data-destructive-confirmation="$destructive ? 'true' : null"
    {{ $attributes }}
>{{ $slot }}</x-admin.confirmation-modal>
