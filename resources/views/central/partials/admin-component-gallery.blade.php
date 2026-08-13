@php
    $galleryRoute = request()->routeIs('central.component-gallery')
        ? 'central.component-gallery'
        : 'dev.component-gallery.capture';
    $galleryUrl = route($galleryRoute, absolute: false);
@endphp

<div class="space-y-admin-section" data-admin-components-fixture="{{ $adminComponentFixture['version'] }}" data-admin-components-section="{{ $componentSection }}">
    <x-admin.page-header
        screen-id="CA-DS-002"
        title="Foundation Component Gallery"
        description="Canonical visual reference for reusable administration components and patterns. Every example renders the production component with deterministic, non-persistent data."
        status="Foundation"
        :breadcrumbs="[]"
    >
        <x-slot:actions>
            <x-ui.action-group label="Gallery sections" align="start" class="flex-wrap">
                @foreach (['actions', 'forms', 'tables', 'indicators', 'layout', 'feedback', 'overlays', 'advanced'] as $section)
                    <x-ui.button variant="{{ $componentSection === $section ? 'primary' : 'secondary' }}" href="{{ $galleryUrl }}?mode=components&section={{ $section }}">{{ ucfirst($section) }}</x-ui.button>
                @endforeach
            </x-ui.action-group>
        </x-slot:actions>
    </x-admin.page-header>

    @if (in_array($componentSection, ['actions', 'catalog', 'acceptance'], true))
        <x-admin.card title="Buttons & Actions" description="Primary, secondary, tertiary, destructive, icon, loading, disabled, row, and bulk action states." data-gallery-actions-fixture>
            <div class="space-y-admin-card">
            <x-ui.action-group label="Button variants" align="start">
                <x-ui.button>Save changes</x-ui.button>
                <x-ui.button variant="secondary">Preview</x-ui.button>
                <x-ui.button variant="tertiary">Cancel</x-ui.button>
                <x-ui.button variant="danger">Delete</x-ui.button>
                <x-ui.button icon="eye" label="View details" />
                <x-ui.button loading>Saving</x-ui.button>
                <x-ui.button disabled>Disabled</x-ui.button>
            </x-ui.action-group>
            <div class="flex flex-wrap items-center justify-between gap-admin-card rounded-admin-input border border-admin-border bg-admin-surface-muted p-admin-card">
                <x-admin.bulk-actions table-id="gallery-action-table" :actions="[['id' => 'archive', 'label' => 'Archive selected']]" />
                <x-admin.row-actions row-id="fixture-row" :actions="[['label' => 'Edit', 'url' => '/admin/central'], ['label' => 'Delete', 'url' => '/admin/central', 'destructive' => true, 'confirmationId' => 'gallery-delete-modal']]" />
            </div>
            <div class="grid gap-admin-section lg:grid-cols-2">
                @foreach ($actionProgressFixture as $state => $progress)
                    <x-ui.states.action-progress :progress="$progress" action-label="Start export" retry-label="Retry export" reset-label="Dismiss result" data-gallery-action-state="{{ $state }}" />
                @endforeach
            </div>
            </div>
        </x-admin.card>
    @endif

    @if (in_array($componentSection, ['forms', 'catalog', 'acceptance'], true))
        <x-admin.card title="Form Controls" description="The accepted foundation appearance for native inputs and form state; no parallel form framework." data-gallery-forms-fixture>
            <x-ui.form.form-state id="gallery-form" class="grid gap-admin-card lg:grid-cols-2">
                <x-ui.form.input id="gallery-name" name="name" label="Brand name" value="Acme Displays" required help="Shown in administrative references." />
                <x-ui.form.slug-input id="gallery-slug" name="slug" label="Slug" prefix="catalog.test/brands/" value="acme-displays" />
                <x-ui.form.textarea id="gallery-description" name="description" label="Description" value="A deterministic fixture." :rows="3" optional />
                <x-ui.form.input id="gallery-error" name="external_id" label="External ID" value="duplicate" error="This identifier is already used." />
                <x-ui.form.select id="gallery-status" name="status" label="Status" :options="$adminComponentFixture['options']" selected="active" />
                <x-ui.form.multi-select id="gallery-markets" name="locales" label="Locales — compact multi-select" :options="['de-DE' => 'German (Germany)', 'en-DE' => 'English (Germany)']" :selected="['de-DE']" help="Compact native variant for short lists." />
                <x-ui.form.checkbox-list id="gallery-market-checkboxes" name="markets" label="Markets — Checkbox list" :options="['de' => 'Germany', 'at' => 'Austria', 'ch' => 'Switzerland']" :selected="['de', 'at']" help="Preferred when all choices should remain visible." />
                <x-ui.form.checkbox-dropdown id="gallery-market-dropdown" name="market_dropdown" label="Markets — Checkbox dropdown" :options="['de' => 'Germany', 'at' => 'Austria', 'ch' => 'Switzerland', 'nl' => 'Netherlands']" :selected="['de', 'at']" help="Compact dropdown for multiple checked choices." />
                <x-ui.form.scrollable-checkbox-list id="gallery-market-scroll" name="market_scroll" label="Markets — Scrollable checkbox list" :options="['de' => 'Germany', 'at' => 'Austria', 'ch' => 'Switzerland', 'nl' => 'Netherlands', 'be' => 'Belgium', 'fr' => 'France', 'it' => 'Italy']" :selected="['de', 'ch']" help="Bounded list for longer option sets." />
                <x-ui.form.checkbox id="gallery-featured" name="featured" label="Featured brand" checked help="Uses a native checkbox." />
                <x-ui.form.toggle id="gallery-visible" name="visible" label="Public visibility" checked />
                <x-ui.form.radio-group id="gallery-source" name="source" label="Data source" :options="['manual' => 'Manual', 'import' => 'Import']" selected="manual" />
                <x-ui.form.date-picker id="gallery-publish-date" name="publish_date" label="Publish date — Calendar picker" value="2026-08-12" min="2026-08-10" max="2026-08-20" help="Modern date-only calendar popup." />
                <x-ui.form.date-time id="gallery-publish-at" name="publish_at" label="Publish at — Date & time picker" value="2026-08-05T13:15" min="2026-08-02T00:00" max="2026-08-20T23:59" timezone="Europe/Sofia" />
                <x-ui.form.file-input id="gallery-file" name="file" label="Reference file" accept="image/png,image/jpeg" hint="PNG or JPEG; selection only, no upload occurs." />
                <x-ui.form.input id="gallery-readonly" name="readonly" label="Read-only field" value="Immutable source value" readonly />
                <x-ui.form.input id="gallery-disabled" name="disabled" label="Disabled field" value="Unavailable in this state" disabled />
                <button type="submit" hidden>Submit fixture</button>
            </x-ui.form.form-state>
        </x-admin.card>
    @endif

    @if (in_array($componentSection, ['tables', 'catalog', 'acceptance'], true))
        <x-admin.card title="Tables" description="Search, filters, active filters, prepared rows, selection, row actions, pagination, empty, and loading states." data-gallery-tables-fixture>
            <div class="space-y-admin-card">
                <x-admin.table-toolbar :action="$galleryUrl" search-id="gallery-search" search="Acme">
                    <input type="hidden" name="mode" value="components">
                    <input type="hidden" name="section" value="tables">
                </x-admin.table-toolbar>
                <x-admin.filter-bar :action="$galleryUrl" drawer-id="gallery-filters">
                    <input type="hidden" name="mode" value="components">
                    <input type="hidden" name="section" value="tables">
                    <input type="hidden" name="q" value="Acme">
                    <x-ui.form.select id="gallery-filter-status" name="status" label="Status" :options="$adminComponentFixture['options']" selected="active" />
                </x-admin.filter-bar>
                <x-admin.active-filters :filters="$adminComponentFixture['filters']" :clear-all-url="$galleryUrl.'?mode=components&section=tables'" />
                <x-admin.bulk-actions table-id="gallery-brands" :actions="[['id' => 'archive', 'label' => 'Archive selected']]" />
                <x-admin.data-table table-id="gallery-brands" caption="Brand-like fixture" :columns="$adminComponentFixture['columns']" :rows="$adminComponentFixture['rows']" selectable />
                <div class="flex justify-end"><x-admin.row-actions row-id="brand-1" :actions="[['label' => 'Edit', 'url' => '/brands/brand-1/edit'], ['label' => 'Delete', 'url' => '/brands/brand-1', 'destructive' => true, 'confirmationId' => 'gallery-delete-modal']]" /></div>
                <x-admin.pagination :previous-url="$galleryUrl.'?mode=components&section=tables&q=Acme&page=1'" :next-url="$galleryUrl.'?mode=components&section=tables&q=Acme&page=3'" :page="2" />
                <div class="grid gap-admin-card lg:grid-cols-2">
                    <x-admin.data-table table-id="gallery-empty-table" caption="Empty fixture" :columns="$adminComponentFixture['columns']" :rows="[]" />
                    <x-ui.states.loading label="Loading table records" :rows="4" />
                </div>
            </div>
        </x-admin.card>
    @endif

    @if (in_array($componentSection, ['indicators', 'catalog', 'acceptance'], true))
        <x-admin.card title="Status / Data Indicators" description="Status, locale/translation, projection, and data-quality semantics remain reusable and explicit." data-gallery-indicators-fixture>
            <div class="space-y-admin-card">
                <div class="flex flex-wrap gap-admin-field">
                    @foreach (['success', 'warning', 'danger', 'info', 'neutral'] as $tone)
                        <x-admin.status-badge :label="ucfirst($tone)" :variant="$tone" />
                    @endforeach
                    <x-ui.status-badge label="UI status wrapper" tone="success" />
                </div>
                <div class="flex flex-wrap gap-admin-field">
                    <x-admin.translation-status-badge status="missing" locale="de-DE" />
                    <x-admin.translation-status-badge status="machine" locale="en-DE" />
                    <x-admin.translation-status-badge status="approved" locale="de-DE" />
                    <x-admin.projection-status-badge status="synced" last-updated="10:15 UTC" />
                    <x-admin.projection-status-badge status="stale" />
                    <x-admin.projection-status-badge status="failed" />
                    <x-admin.quality-warning-badge level="low" label="Optional metadata" />
                    <x-admin.quality-warning-badge level="critical" label="Required value missing" :count="2" />
                </div>
                <div class="grid gap-admin-field sm:grid-cols-3">
                    <x-ui.identifier value="BR-0042" label="Record code" />
                    <x-ui.timestamp :value="$adminComponentFixture['timestamp']" timezone="Europe/Sofia" relative-hint="Fixed fixture time" />
                    <x-ui.reference label="Tech Germany" kind="Site" url="/sites/tech-de" />
                </div>
            </div>
        </x-admin.card>
    @endif

    @if (in_array($componentSection, ['layout', 'catalog', 'acceptance'], true))
        <div class="space-y-admin-section" data-gallery-layout-fixture>
            <x-admin.card title="Layout" description="Page header, breadcrumbs, tabs, cards, two-column detail composition, and a sticky action region.">
                <x-admin.detail-layout>
                    <x-slot:main>
                        <x-admin.card title="Detail layout">
                            <x-admin.tabs :items="[['key' => 'general', 'label' => 'General', 'url' => '#general'], ['key' => 'locales', 'label' => 'Locales', 'url' => '#locales', 'count' => 2]]" active="general" />
                            <x-ui.section title="Record identity" description="Reusable section hierarchy." class="mt-admin-card">
                                <p class="text-sm text-admin-muted">The full-width workspace allows this local composition to choose its own readable columns.</p>
                            </x-ui.section>
                        </x-admin.card>
                    </x-slot:main>
                    <x-slot:aside><x-admin.card title="Reference"><x-ui.reference label="Tech Germany" kind="Site" /></x-admin.card></x-slot:aside>
                    <x-slot:actions><x-ui.action-group><x-ui.button variant="secondary">Cancel</x-ui.button><x-ui.button>Save</x-ui.button></x-ui.action-group></x-slot:actions>
                </x-admin.detail-layout>
            </x-admin.card>
        </div>
    @endif

    @if (in_array($componentSection, ['feedback', 'catalog', 'acceptance'], true))
        <x-admin.card title="Feedback" description="Success, warning, error, empty, filtered-empty, retry, validation, and loading states." data-gallery-feedback-fixture>
            <div class="space-y-admin-card">
                <div class="grid gap-admin-field lg:grid-cols-3">
                    <x-ui.alert tone="success" title="Saved" message="The deterministic fixture was accepted." />
                    <x-ui.alert tone="warning" title="Review needed" message="Two localized labels are incomplete." />
                    <x-ui.alert tone="danger" title="Could not save" message="No data changed; retry after correcting the form." />
                </div>
                <x-ui.alert tone="warning" title="Unsaved changes" message="The form-state primitive warns before leaving a dirty form." />
                <x-ui.toast tone="success" message="Draft saved." dismissible />
                <div class="grid gap-admin-section lg:grid-cols-2">
                    <x-admin.empty-state title="No foundation records" description="The reusable admin empty-state pattern remains available for future record screens." />
                    <x-ui.states.empty id="gallery-empty-state" title="No records yet" message="Create the first record when an approved source is available." action-label="Create record" action-url="/admin/central" />
                    <x-ui.states.filtered-empty id="gallery-filtered-empty-state" title="No matching records" message="No records match the current search and filters." :clear-url="$galleryUrl.'?mode=components&section=feedback'" />
                    <x-ui.retry-block message="Preview could not be loaded." retry-label="Retry preview" />
                    <x-ui.form.input id="gallery-feedback-validation" name="validation" label="Validation state" value="duplicate" error="This identifier is already used." />
                </div>
            </div>
        </x-admin.card>
    @endif

    @if ($componentSection === 'states')
        <x-admin.card title="Feedback" description="Empty, filtered-empty, and loading states remain semantically distinct." data-gallery-states-fixture>
            <div class="grid gap-admin-section lg:grid-cols-2">
                <x-ui.states.empty id="gallery-legacy-empty-state" title="No records yet" message="Create the first record after an approved source is available." action-label="Create record" action-url="/admin/central" />
                <x-ui.states.filtered-empty id="gallery-legacy-filtered-state" title="No matching records" message="No records match the current search and filters." :clear-url="$galleryUrl.'?mode=components&section=states'" />
                <x-ui.states.loading label="Loading records" :rows="4" class="lg:col-span-2" />
            </div>
        </x-admin.card>
    @endif

    @if (in_array($componentSection, ['overlays', 'catalog', 'acceptance'], true))
        @if ($componentAcceptance && $componentSection === 'acceptance')
            <button type="button" class="sr-only" data-admin-modal-open-target="gallery-modal">Open modal for acceptance</button>
            <button type="button" class="sr-only" data-admin-modal-open-target="gallery-delete-modal">Open confirmation for acceptance</button>
        @endif
        <x-admin.card title="Overlays" description="Modal, confirmation, destructive confirmation, and drawer patterns; all demo actions are inert." data-gallery-overlays-fixture>
            <div class="grid gap-admin-section xl:grid-cols-3">
                <div class="relative min-h-80 overflow-hidden rounded-admin-card border border-admin-border" data-gallery-modal-fixture>
                    <x-ui.modal id="gallery-modal" title="Brand details" open contained>
                        This contained dialog demonstrates focus ownership without covering the gallery.
                        <x-slot:footer><x-ui.button variant="secondary" data-admin-modal-close>Close</x-ui.button></x-slot:footer>
                    </x-ui.modal>
                </div>
                <div class="relative min-h-80 overflow-hidden rounded-admin-card border border-admin-border" data-gallery-confirmation-fixture>
                    <x-ui.confirmation-dialog id="gallery-delete-modal" title="Delete brand" message="This action cannot be undone." confirm-label="Delete" destructive open contained />
                </div>
                <div class="relative min-h-80 overflow-hidden rounded-admin-card border border-admin-border" data-gallery-drawer-fixture>
                    <x-admin.drawer title="Record filters" open contained size="sm">
                        <x-ui.form.checkbox id="gallery-drawer-active" name="active" label="Active only" checked />
                        <x-slot:actions><x-ui.button variant="secondary">Apply filters</x-ui.button></x-slot:actions>
                    </x-admin.drawer>
                </div>
            </div>
        </x-admin.card>
    @endif

    @if (in_array($componentSection, ['advanced', 'catalog', 'acceptance'], true))
        <x-admin.card title="Existing Higher-level Reusable Components" description="Existing composition primitives for localized values, measurements, media, diffs, imports, and review flows." data-gallery-advanced-fixture>
            <div class="space-y-admin-section">
                <x-admin.localized-field-editor
                    field-name="Display name"
                    :locales="[['code' => 'de-DE', 'label' => 'Deutsch'], ['code' => 'en-DE', 'label' => 'English']]"
                    :values="['de-DE' => 'Deterministische Anzeige', 'en-DE' => 'Deterministic display']"
                    :statuses="['de-DE' => 'approved', 'en-DE' => 'reviewed']"
                />
                <div class="grid gap-admin-section lg:grid-cols-2">
                    <x-admin.card title="Unit/value input">
                        <x-admin.unit-value-input id="gallery-screen-size" label="Screen size" value="27" unit="in" :available-units="[['value' => 'in', 'label' => 'inch'], ['value' => 'cm', 'label' => 'cm']]" canonical-preview="68.58 cm" />
                    </x-admin.card>
                    <x-admin.media-picker mode="multiple" :accepted-types="['image/png', 'image/jpeg']" :selected-items="[['name' => 'Front view', 'type' => 'image'], ['name' => 'Side view', 'type' => 'image']]" />
                </div>
                <x-admin.diff-viewer field-label="Display name" before-label="Current" before-value="Acme Display" after-label="Proposed" after-value="Acme Displays" variant="side-by-side" />
                <x-admin.attribute-value-editor
                    attribute-label="Screen diagonal"
                    attribute-code="screen_diagonal"
                    data-type="unit"
                    raw-value="27 inch"
                    normalized-value="27"
                    :unit-options="[['value' => 'in', 'label' => 'inch'], ['value' => 'cm', 'label' => 'cm']]"
                    :confidence="96"
                    source-label="Deterministic fixture"
                />
                <x-admin.import-progress-panel
                    source-name="Deterministic import fixture"
                    category-name="Foundation preview only"
                    status="completed"
                    :steps="[['label' => 'Upload', 'status' => 'completed'], ['label' => 'Validate', 'status' => 'completed'], ['label' => 'Review', 'status' => 'current']]"
                    :stats="[['label' => 'Read', 'value' => 12], ['label' => 'Valid', 'value' => 10], ['label' => 'Warnings', 'value' => 2], ['label' => 'Errors', 'value' => 0]]"
                />
                <div class="grid gap-admin-section xl:grid-cols-2">
                    <x-admin.change-request-card request-title="Localized label update" requester-label="Site editor" source-site-label="Tech Germany" entity-label="Fixture record" field-label="Display name" current-value="Display" proposed-value="Displays" status="needs_info" submitted-at="2026-08-05 10:15 UTC" :actions="[['label' => 'Review later']]" />
                    <x-admin.conflict-review-card title="Source conflict" entity-label="Fixture record" field-label="Manufacturer code" source-a="Primary source" source-b="Secondary source" value-a="ACME" value-b="ACME-DE" severity="medium" :actions="[['label' => 'Resolve later']]" />
                </div>
                <div class="relative min-h-80 overflow-hidden rounded-admin-card border border-admin-border">
                    <x-admin.confirmation-modal id="gallery-foundation-confirmation" title="Archive record" message="The reusable admin confirmation pattern performs no action in this gallery." confirm-label="Archive" variant="warning" open contained />
                </div>
            </div>
        </x-admin.card>
    @endif

    @if ($componentAcceptance && $componentSection === 'acceptance')
        <output data-browser-acceptance="pending" class="sr-only">pending</output>
        <script>
            window.addEventListener('load', () => {
                try {
                    const row = document.querySelector('[data-admin-row-select]');
                    row?.click();
                    const selected = document.querySelector('[data-admin-bulk-actions="gallery-brands"] [data-selected-count]')?.textContent;
                    if (selected !== '1') throw new Error('selection');
                    const selectedTable = row?.closest('table');
                    const independentTable = selectedTable?.cloneNode(true);
                    if (independentTable) {
                        independentTable.id = 'gallery-brands-independent';
                        document.body.append(independentTable);
                        selectedTable.dispatchEvent(new CustomEvent('admin:table-state-changed', { bubbles: true }));
                        if (row.checked || ! independentTable.querySelector('[data-admin-row-select]')?.checked) throw new Error('selection-scope');
                        independentTable.remove();
                    }

                    document.querySelector('[data-admin-form-state] input')?.dispatchEvent(new Event('input', { bubbles: true }));
                    const form = document.querySelector('[data-admin-form-state]');
                    if (form?.dataset.adminFormDirty !== 'true') throw new Error('form-dirty');
                    const unload = new Event('beforeunload', { cancelable: true });
                    window.dispatchEvent(unload);
                    if (! unload.defaultPrevented) throw new Error('form-warning');
                    form?.addEventListener('submit', (event) => event.preventDefault(), { once: true });
                    form?.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true }));
                    const duplicateSubmit = new SubmitEvent('submit', { bubbles: true, cancelable: true });
                    form?.dispatchEvent(duplicateSubmit);
                    if (! duplicateSubmit.defaultPrevented || form?.dataset.adminFormSubmitting !== 'true') throw new Error('form-submit');
                    form?.dispatchEvent(new CustomEvent('admin:form-saved', { bubbles: true }));
                    if (form?.dataset.adminFormDirty !== 'false' || form?.querySelector('[type="submit"]')?.disabled) throw new Error('form-saved');
                    const intentionallyDisabledSubmit = document.createElement('button');
                    intentionallyDisabledSubmit.type = 'submit';
                    intentionallyDisabledSubmit.disabled = true;
                    form?.append(intentionallyDisabledSubmit);
                    const implicitSubmit = document.createElement('button');
                    form?.append(implicitSubmit);
                    form?.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true }));
                    if (! implicitSubmit.disabled) throw new Error('form-implicit-submit');
                    form?.querySelector('input')?.dispatchEvent(new Event('input', { bubbles: true }));
                    form?.dispatchEvent(new CustomEvent('admin:form-saved', { bubbles: true }));
                    if (form?.dataset.adminFormDirty !== 'true' || ! intentionallyDisabledSubmit.disabled || implicitSubmit.disabled) throw new Error('form-concurrent-edit');

                    const firstTab = document.querySelector('[data-admin-tabs] [role="tab"]');
                    firstTab?.focus();
                    firstTab?.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }));
                    if (document.activeElement === firstTab || firstTab?.tabIndex !== -1 || document.activeElement?.tabIndex !== 0) throw new Error('tabs');

                    const filterOpen = document.querySelector('[data-admin-filter-open]');
                    filterOpen.style.display = 'block';
                    filterOpen?.focus();
                    filterOpen?.click();
                    const filterDrawer = document.querySelector('[data-admin-filter-drawer]');
                    if (filterDrawer?.classList.contains('hidden')) throw new Error('filter-open');
                    const toolbarQuery = new FormData(document.querySelector('[data-admin-table-toolbar]'));
                    const filterQuery = new FormData(filterDrawer);
                    if (toolbarQuery.get('mode') !== 'components' || toolbarQuery.get('section') !== 'tables') throw new Error('toolbar-query');
                    if (filterQuery.get('mode') !== 'components' || filterQuery.get('section') !== 'tables') throw new Error('filter-query');
                    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
                    if (! filterDrawer?.classList.contains('hidden') || document.activeElement !== filterOpen) throw new Error('filter-close');
                    const secondFilterOpen = document.createElement('button');
                    secondFilterOpen.dataset.adminFilterOpen = 'second-gallery-filters';
                    secondFilterOpen.setAttribute('aria-expanded', 'false');
                    const secondFilterDrawer = document.createElement('form');
                    secondFilterDrawer.id = 'second-gallery-filters';
                    secondFilterDrawer.dataset.adminFilterDrawer = '';
                    secondFilterDrawer.dataset.adminFilterOpen = 'false';
                    secondFilterDrawer.classList.add('hidden');
                    secondFilterDrawer.append(document.createElement('input'));
                    document.body.append(secondFilterOpen, secondFilterDrawer);
                    filterOpen?.click();
                    secondFilterOpen.click();
                    if (filterDrawer?.dataset.adminFilterOpen !== 'false' || secondFilterDrawer.dataset.adminFilterOpen !== 'true') throw new Error('filter-single-open');
                    window.dispatchEvent(new Event('resize'));
                    if (secondFilterDrawer.dataset.adminFilterOpen !== 'false' || document.body.classList.contains('admin-filter-drawer-open')) throw new Error('filter-resize');
                    secondFilterOpen.remove();
                    secondFilterDrawer.remove();

                    const modal = document.getElementById('gallery-modal');
                    modal?.querySelector('[data-admin-modal-close]')?.click();
                    document.getElementById('gallery-delete-modal')?.querySelector('[data-admin-modal-close]')?.click();
                    const modalTrigger = document.querySelector('[data-admin-modal-open-target="gallery-modal"]');
                    modalTrigger?.click();
                    if (! modal?.querySelector('[role="dialog"]')?.contains(document.activeElement)) throw new Error('modal-focus');
                    const outsideFocus = document.createElement('button');
                    document.body.append(outsideFocus);
                    outsideFocus.focus();
                    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true }));
                    if (! modal?.querySelector('[role="dialog"]')?.contains(document.activeElement)) throw new Error('modal-trap');
                    outsideFocus.remove();
                    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
                    if (modal?.dataset.adminModalOpen !== 'false' || document.activeElement !== modalTrigger) throw new Error('modal');

                    modalTrigger?.click();
                    const confirmationTrigger = document.querySelector('[data-admin-modal-open-target="gallery-delete-modal"]');
                    confirmationTrigger?.click();
                    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
                    if (document.getElementById('gallery-delete-modal')?.dataset.adminModalOpen !== 'false' || modal?.dataset.adminModalOpen !== 'true') throw new Error('modal-order');
                    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));

                    const emptyModalTrigger = document.createElement('button');
                    emptyModalTrigger.dataset.adminModalOpenTarget = 'empty-gallery-modal';
                    const emptyModal = document.createElement('div');
                    emptyModal.id = 'empty-gallery-modal';
                    emptyModal.dataset.adminModal = 'empty-gallery-modal';
                    emptyModal.dataset.adminModalOpen = 'false';
                    emptyModal.classList.add('hidden');
                    const emptyDialog = document.createElement('section');
                    emptyDialog.setAttribute('role', 'dialog');
                    emptyModal.append(emptyDialog);
                    document.body.append(emptyModalTrigger, emptyModal);
                    emptyModalTrigger.click();
                    if (document.activeElement !== emptyDialog) throw new Error('modal-empty-focus');
                    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
                    emptyModalTrigger.remove();
                    emptyModal.remove();

                    confirmationTrigger?.click();
                    const confirm = document.querySelector('#gallery-delete-modal [data-admin-modal-confirm]');
                    confirm?.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
                    const duplicateConfirm = new MouseEvent('click', { bubbles: true, cancelable: true });
                    confirm?.dispatchEvent(duplicateConfirm);
                    if (! duplicateConfirm.defaultPrevented || confirm?.getAttribute('aria-busy') !== 'true') throw new Error('confirmation');

                    const toast = document.querySelector('[data-ui-toast]');
                    toast?.querySelector('[data-ui-feedback-dismiss]')?.click();
                    if (document.contains(toast)) throw new Error('feedback-dismiss');
                    let retries = 0;
                    const retry = document.querySelector('[data-ui-retry]');
                    retry?.addEventListener('click', () => retries++);
                    retry?.click();
                    if (retries !== 1) throw new Error('feedback-retry');

                    const actionProgress = document.querySelector('[data-gallery-action-state="idle"]');
                    const actionStart = actionProgress?.querySelector('[data-action-progress-start]');
                    let actionStarts = 0;
                    actionProgress?.addEventListener('admin:action-progress-start', () => actionStarts++);
                    actionStart?.click();
                    actionStart?.click();
                    if (actionStarts !== 1 || ! actionStart?.disabled || actionProgress?.dataset.actionProgressStarted !== 'true') throw new Error('action-progress-duplicate');

                    const marker = document.querySelector('[data-browser-acceptance]');
                    marker.dataset.browserAcceptance = 'passed';
                    marker.textContent = 'passed';
                } catch (error) {
                    const marker = document.querySelector('[data-browser-acceptance]');
                    marker.dataset.browserAcceptance = 'failed';
                    marker.textContent = String(error);
                }
            });
        </script>
    @endif
</div>
