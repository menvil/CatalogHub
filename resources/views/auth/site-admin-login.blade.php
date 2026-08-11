<x-filament-panels::page.simple>
    <div class="cataloghub-auth-screen" data-auth-screen="site-admin-login" data-presentation-context="site-admin">
        <p class="cataloghub-auth-context">Site administration</p>
        {{ $this->content }}
        <p class="cataloghub-auth-notice">Site access is selected only after authentication.</p>
    </div>
</x-filament-panels::page.simple>
