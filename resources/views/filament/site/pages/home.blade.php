<div class="space-y-admin-section" data-presentation-context="site-admin">
    <x-admin.page-header
        screen-id="SA-001"
        title="Site dashboard"
        description="Foundation shell state for the selected site. No business metrics are displayed."
        status="Foundation"
        :breadcrumbs="[
            [
                'label' => 'Site Admin',
                'url' => route('filament.site.pages.home', ['site_id' => $this->siteId], absolute: false),
            ],
            ['label' => 'Dashboard'],
        ]"
    />

    <x-admin.empty-state
        title="Site Admin shell is ready"
        description="No site metrics are available in the foundation shell. Available sections can be opened from the navigation."
    />
</div>
