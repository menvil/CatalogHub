<details class="relative" data-central-user-menu>
    <summary
        class="flex cursor-pointer list-none items-center gap-2 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm font-medium text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
    >
        <x-ui.icon name="user-circle" size="sm" />
        <span class="max-w-40 truncate">{{ $user->name }}</span>
    </summary>

    <div class="absolute right-0 z-30 mt-2 min-w-56 rounded-admin-card border border-admin-border bg-admin-surface p-2 shadow-admin-floating">
        <p class="truncate px-2 py-1 text-xs text-admin-muted">{{ $user->email }}</p>

        @if (app()->environment(['local', 'testing']))
            <p class="px-2 py-1 text-xs font-medium text-admin-muted" data-central-environment>
                Environment: {{ app()->environment() }}
            </p>
        @endif

        <form method="POST" action="{{ route('filament.central.auth.logout', absolute: false) }}" class="mt-1">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center gap-2 rounded-admin-input px-2 py-2 text-left text-sm font-medium text-admin-text hover:bg-admin-surface-muted focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
            >
                <x-ui.icon name="arrow-left-start-on-rectangle" size="sm" />
                <span>Log out</span>
            </button>
        </form>
    </div>
</details>
