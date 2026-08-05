<div class="space-y-admin-section" data-presentation-context="central-admin">
    <x-admin.page-header
        screen-id="CA-001"
        title="Central dashboard"
        description="Foundation shell state for Central administration. No business metrics are displayed."
        status="Foundation"
        :breadcrumbs="[['label' => 'Dashboard']]"
    />

    <x-admin.empty-state
        title="Central Admin shell is ready"
        description="No metrics are available in the foundation shell. Available sections can be opened from the navigation."
    />
</div>
