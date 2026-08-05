<div class="space-y-admin-section" data-admin-components-fixture="{{ $adminComponentFixture['version'] }}" data-admin-components-section="{{ $componentSection }}">
    <x-admin.page-header
        screen-id="CA-DS-002"
        title="Admin component gallery"
        description="Deterministic presentation fixtures."
        status="Foundation"
        :breadcrumbs="[['label' => 'Design system', 'url' => '/dev/component-gallery'], ['label' => ucfirst($componentSection)]]"
    >
        <x-slot:actions>
            <x-ui.action-group label="Gallery sections">
                @foreach (['forms', 'tables', 'feedback'] as $section)
                    <x-ui.button variant="{{ $componentSection === $section ? 'primary' : 'secondary' }}" href="/dev/component-gallery?mode=components&amp;section={{ $section }}">{{ ucfirst($section) }}</x-ui.button>
                @endforeach
            </x-ui.action-group>
        </x-slot:actions>
    </x-admin.page-header>

    @if (in_array($componentSection, ['forms', 'acceptance'], true))
        <x-admin.card title="Buttons and form controls" description="Default, error, disabled, loading, and grouped action states.">
            <x-ui.action-group label="Button variants" align="start" class="mb-admin-section">
                <x-ui.button>Save changes</x-ui.button>
                <x-ui.button variant="secondary">Preview</x-ui.button>
                <x-ui.button variant="tertiary">Cancel</x-ui.button>
                <x-ui.button variant="danger">Delete</x-ui.button>
                <x-ui.button loading>Saving</x-ui.button>
                <x-ui.button disabled>Disabled</x-ui.button>
            </x-ui.action-group>

            <x-ui.form.form-state id="gallery-form" class="grid gap-admin-card lg:grid-cols-2">
                <x-ui.form.input id="gallery-name" name="name" label="Brand name" value="Acme Displays" required help="Shown in administrative references." />
                <x-ui.form.slug-input id="gallery-slug" name="slug" label="Slug" prefix="catalog.test/brands/" value="acme-displays" />
                <x-ui.form.textarea id="gallery-description" name="description" label="Description" value="A deterministic fixture." :rows="3" optional />
                <x-ui.form.input id="gallery-error" name="external_id" label="External ID" value="duplicate" error="This identifier is already used." />
                <x-ui.form.select id="gallery-status" name="status" label="Status" :options="$adminComponentFixture['options']" selected="active" />
                <x-ui.form.multi-select id="gallery-markets" name="markets" label="Markets" :options="['de' => 'Germany', 'at' => 'Austria', 'ch' => 'Switzerland']" :selected="['de', 'at']" />
                <x-ui.form.checkbox id="gallery-featured" name="featured" label="Featured brand" checked help="Uses a native checkbox." />
                <x-ui.form.toggle id="gallery-visible" name="visible" label="Public visibility" checked />
                <x-ui.form.radio-group id="gallery-source" name="source" label="Data source" :options="['manual' => 'Manual', 'import' => 'Import']" selected="manual" />
                <x-ui.form.date-time id="gallery-publish-at" name="publish_at" label="Publish at" value="2026-08-05T13:15" timezone="Europe/Sofia" />
                <x-ui.form.file-input id="gallery-file" name="file" label="Reference file" accept="image/png,image/jpeg" hint="PNG or JPEG; selection only, no upload occurs." />
                <x-ui.form.input id="gallery-disabled" name="disabled" label="Disabled field" value="Read only state" disabled />
            </x-ui.form.form-state>
        </x-admin.card>

        <x-admin.detail-layout>
            <x-slot:main>
                <x-admin.card title="Detail layout">
                    <x-admin.tabs :items="[['key' => 'general', 'label' => 'General', 'url' => '#general'], ['key' => 'locales', 'label' => 'Locales', 'url' => '#locales', 'count' => 2]]" active="general" />
                    <x-ui.section title="Brand identity" description="Reusable section hierarchy." class="mt-admin-card">
                        <p class="text-sm text-admin-muted">Main content remains independent from persistence and query logic.</p>
                    </x-ui.section>
                </x-admin.card>
            </x-slot:main>
            <x-slot:aside><x-admin.card title="Reference"><x-ui.reference label="Tech Germany" kind="Site" /></x-admin.card></x-slot:aside>
            <x-slot:actions><x-ui.action-group><x-ui.button variant="secondary">Cancel</x-ui.button><x-ui.button>Save</x-ui.button></x-ui.action-group></x-slot:actions>
        </x-admin.detail-layout>
    @endif

    @if (in_array($componentSection, ['tables', 'acceptance'], true))
        <x-admin.card title="Table query and filter contracts" description="The fixture receives prepared rows and URL state; it performs no query.">
            <div class="space-y-admin-card">
                <x-admin.table-toolbar action="/dev/component-gallery" search-id="gallery-search" search="Acme" />
                <x-admin.filter-bar action="/dev/component-gallery" drawer-id="gallery-filters">
                    <x-ui.form.select id="gallery-filter-status" name="status" label="Status" :options="$adminComponentFixture['options']" selected="active" />
                </x-admin.filter-bar>
                <x-admin.active-filters :filters="$adminComponentFixture['filters']" clear-all-url="/dev/component-gallery?mode=components&section=tables" />
                <x-admin.bulk-actions table-id="gallery-brands" :actions="[['id' => 'archive', 'label' => 'Archive selected']]" />
                <x-admin.data-table table-id="gallery-brands" caption="Brand-like fixture" :columns="$adminComponentFixture['columns']" :rows="$adminComponentFixture['rows']" selectable />
                <div class="flex justify-end"><x-admin.row-actions row-id="brand-1" :actions="[['label' => 'Edit', 'url' => '/brands/brand-1/edit'], ['label' => 'Delete', 'url' => '/brands/brand-1', 'destructive' => true]]" /></div>
                <x-admin.pagination previous-url="/brands?page=1" next-url="/brands?page=3" :page="2" />
            </div>
        </x-admin.card>
    @endif

    @if (in_array($componentSection, ['feedback', 'acceptance'], true))
        @if ($componentAcceptance)
            <button type="button" class="sr-only" data-admin-modal-open-target="gallery-modal">Open modal for acceptance</button>
        @endif
        <div class="grid gap-admin-section lg:grid-cols-2">
            <x-admin.card title="Data display and feedback" class="order-2 lg:order-1">
                <div class="space-y-admin-card">
                    <div class="flex flex-wrap gap-admin-field">
                        @foreach (['success', 'warning', 'danger', 'info', 'neutral'] as $tone)
                            <x-ui.status-badge :label="ucfirst($tone)" :tone="$tone" />
                        @endforeach
                    </div>
                    <div class="grid gap-admin-field sm:grid-cols-3">
                        <x-ui.identifier value="BR-0042" label="Brand code" />
                        <x-ui.timestamp :value="$adminComponentFixture['timestamp']" timezone="Europe/Sofia" relative-hint="Fixed fixture time" />
                        <x-ui.reference label="Tech Germany" kind="Site" url="/sites/tech-de" />
                    </div>
                    <x-ui.alert tone="warning" title="Review needed" message="Two localized labels are incomplete." />
                    <x-ui.toast tone="success" message="Draft saved." dismissible />
                    <x-ui.retry-block message="Preview could not be loaded." retry-label="Retry preview" />
                </div>
            </x-admin.card>

            <div class="order-1 space-y-admin-section lg:order-2">
                <div class="relative min-h-80 overflow-hidden rounded-admin-card border border-admin-border" data-gallery-modal-fixture>
                    <x-ui.modal id="gallery-modal" title="Brand details" open contained>
                        This contained dialog demonstrates focus ownership without covering the gallery.
                        <x-slot:footer><x-ui.button variant="secondary" data-admin-modal-close>Close</x-ui.button></x-slot:footer>
                    </x-ui.modal>
                </div>
                <div class="relative min-h-80 overflow-hidden rounded-admin-card border border-admin-border" data-gallery-confirmation-fixture>
                    <x-ui.confirmation-dialog id="gallery-delete-modal" title="Delete brand" message="This action cannot be undone." confirm-label="Delete" destructive open contained />
                </div>
            </div>
        </div>
    @endif

    @if ($componentAcceptance)
        <output data-browser-acceptance="pending" class="sr-only">pending</output>
        <script>
            window.addEventListener('load', () => {
                try {
                    const row = document.querySelector('[data-admin-row-select]');
                    row?.click();
                    const selected = document.querySelector('[data-selected-count]')?.textContent;
                    if (selected !== '1') throw new Error('selection');

                    document.querySelector('[data-admin-form-state] input')?.dispatchEvent(new Event('input', { bubbles: true }));
                    if (document.querySelector('[data-admin-form-state]')?.dataset.adminFormDirty !== 'true') throw new Error('form');

                    const firstTab = document.querySelector('[data-admin-tabs] [role="tab"]');
                    firstTab?.focus();
                    firstTab?.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }));
                    if (document.activeElement === firstTab) throw new Error('tabs');

                    const filterOpen = document.querySelector('[data-admin-filter-open]');
                    filterOpen?.click();
                    const filterDrawer = document.querySelector('[data-admin-filter-drawer]');
                    if (filterDrawer?.classList.contains('hidden')) throw new Error('filter-open');
                    filterDrawer?.querySelector('[data-admin-filter-close]')?.click();
                    if (! filterDrawer?.classList.contains('hidden')) throw new Error('filter-close');

                    const modal = document.getElementById('gallery-modal');
                    modal?.querySelector('[data-admin-modal-close]')?.click();
                    document.getElementById('gallery-delete-modal')?.querySelector('[data-admin-modal-close]')?.click();
                    const modalTrigger = document.querySelector('[data-admin-modal-open-target="gallery-modal"]');
                    modalTrigger?.click();
                    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
                    if (modal?.dataset.adminModalOpen !== 'false' || document.activeElement !== modalTrigger) throw new Error('modal');

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
