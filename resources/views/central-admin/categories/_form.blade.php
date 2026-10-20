@php
    $editing = $mode === 'edit';
    $category = $form->category;
    // After a rejected submit every value, including expected-state tokens and a cleared (null)
    // parent, comes from that submission; fresh values would silently re-base a stale form.
    $submitted = session()->hasOldInput();
    $scalar = static function (string $key, mixed $default) use ($submitted): mixed {
        if (! $submitted) {
            return $default;
        }
        $value = old($key);

        return $value === null || is_scalar($value) ? $value : $default;
    };
    $name = (string) $scalar('name', $category?->name);
    $slug = (string) $scalar('slug', $category?->slug);
    $validParents = collect($form->parentOptions)->pluck('value')->all();
    $requestedParent = $scalar('parent_id', $form->parentId);
    // Never present an untrusted, ineligible ID as an apparently valid selection.
    $selectedParent = $requestedParent === null || $requestedParent === '' || in_array((string) $requestedParent, $validParents, true)
        ? ($requestedParent === '' ? null : $requestedParent) : $form->parentId;
    $siblingIds = array_column($form->siblings, 'id');
    $oldSiblings = $submitted ? old('sibling_ids') : null;
    $submittedSiblings = is_array($oldSiblings) && $oldSiblings !== [] && array_filter($oldSiblings, static fn (mixed $id): bool => ! is_scalar($id) || ! ctype_digit((string) $id)) === []
        ? array_map('intval', array_values($oldSiblings)) : $siblingIds;
    $position = $form->position();
    $requestedPosition = $scalar('display_position', $position);
    $displayPosition = is_numeric($requestedPosition) && (int) $requestedPosition >= 0 && (int) $requestedPosition < count($siblingIds) ? (int) $requestedPosition : $position;
    $positionOptions = [];
    foreach ($form->siblings as $index => $sibling) {
        $positionOptions[(string) $index] = ($index + 1).' of '.count($form->siblings).($index === $position ? ' — current position' : ($index < $position ? ' — before '.$sibling['name'] : ' — after '.$sibling['name']));
    }
    $fieldErrors = ['name', 'slug', 'parent_id', 'display_position', 'ordered_ids', 'position', 'slug_impact', 'slug_impact_acknowledged'];
    $generalErrors = collect($errors->getMessages())->except($fieldErrors)->flatten()->unique()->values();
    $orderError = collect([$errors->first('display_position'), $errors->first('ordered_ids'), $errors->first('position')])->filter()->implode(' ');
@endphp

<x-ui.form.form-state
    id="category-form"
    :action="$action"
    :method="$method"
    :leave-warning="false"
    class="category-form"
    data-category-form="{{ $mode }}"
    data-screen-region="category-editor"
    :data-slug-impact-url="$editing ? route('central.categories.slug-impact', $category) : null"
    :data-current-slug="$editing ? $category->slug : null"
    :data-original-parent="$editing ? (string) $form->parentId : null"
>
    @if ($generalErrors->isNotEmpty())
        <div class="category-form-alert" role="alert" data-category-form-errors>
            <h2>The Category was not saved</h2>
            <ul>
                @foreach ($generalErrors as $message)<li>{{ $message }}</li>@endforeach
            </ul>
            @if ($editing)
                <p>Your entries are kept below. <a href="{{ route('central.categories.edit', $category) }}">Discard them and reload the current Category</a>.</p>
            @else
                <p>Your entries are kept below. <a href="{{ route('central.categories.create') }}">Reload the form</a> to refresh the hierarchy.</p>
            @endif
        </div>
    @endif

    <div class="category-form-layout">
        <div class="category-form-main">
            <x-admin.card title="Category details" description="Canonical source identity and placement in the shared hierarchy." data-screen-region="category-fields">
                <div class="category-form-fields">
                    <x-ui.form.searchable-select
                        id="category-parent"
                        name="parent_id"
                        label="Parent Category"
                        :options="$form->parentOptions"
                        :selected="$selectedParent"
                        :error="$errors->first('parent_id')"
                        placeholder="Root Category (no parent)"
                        search-placeholder="Search by name, path, slug or #ID"
                        :help="$editing ? 'Leave empty for a root Category. Moving appends this Category to the end of the new parent. It and its descendants cannot be chosen.' : 'Leave empty for a root Category. New Categories are appended to the end of the selected parent.'"
                        empty-message="No eligible parent Category matches."
                        clearable
                        data-category-parent
                    />

                    <div class="category-form-row">
                        <x-ui.form.input
                            id="category-name"
                            name="name"
                            label="Category Name"
                            :value="$name"
                            :error="$errors->first('name')"
                            help="Canonical source/reference name. Localized names are translated separately."
                            maxlength="255"
                            required
                            :autofocus="! $editing"
                            data-category-name
                        />
                        <x-ui.form.slug-input
                            id="category-slug"
                            name="slug"
                            label="Slug"
                            :value="$slug"
                            :error="$errors->first('slug')"
                            :help="$editing ? 'Canonical URL key. Changing it can affect public Category URLs; the Category ID stays stable and no redirect is created.' : 'Canonical URL key. Suggested from the name until you edit it.'"
                            maxlength="255"
                            required
                            autocomplete="off"
                            spellcheck="false"
                            data-category-slug
                        />
                    </div>

                    @if ($editing)
                        <div class="category-form-impact" data-screen-region="slug-impact">
                            @if ($errors->first('slug_impact'))<p class="category-form-error" role="alert" data-slug-impact-error>{{ $errors->first('slug_impact') }}</p>@endif
                            <div data-category-slug-impact-container aria-live="polite">
                                @include('central-admin.categories._slug-impact', ['impact' => $slugImpact])
                            </div>
                        </div>
                    @endif

                    <section class="category-form-order" aria-labelledby="category-order-heading" data-screen-region="display-order">
                        <h3 id="category-order-heading">Display Order</h3>
                        @if ($editing && $position !== null)
                            <x-ui.form.select
                                id="category-display-position"
                                name="display_position"
                                label="Position among siblings"
                                :options="$positionOptions"
                                :selected="(string) $displayPosition"
                                :error="$orderError !== '' ? $orderError : null"
                                help="Saves the complete sibling order of the current parent at once. To move under another parent, change the parent first; the order can be adjusted in a later save."
                                data-category-display-position
                            />
                            <p class="category-form-note" data-category-order-parent-note hidden>A new parent was selected. This Category will be appended to the end of the new parent; display order stays unchanged in this save.</p>
                            @unless ($form->siblingOrderContiguous)
                                <p class="category-form-note" data-category-order-repair>The stored sibling positions are not contiguous. Saving a new position rewrites the complete sibling order.</p>
                            @endunless
                            <ol class="category-form-siblings" aria-label="Current sibling order">
                                @foreach ($form->siblings as $index => $sibling)
                                    <li @if ($sibling['id'] === $category->id) aria-current="true" @endif><span>{{ $index + 1 }}</span>{{ $sibling['name'] }}</li>
                                @endforeach
                            </ol>
                            @foreach ($submittedSiblings as $siblingId)
                                <input type="hidden" name="sibling_ids[]" value="{{ $siblingId }}">
                            @endforeach
                        @elseif ($editing)
                            <p class="category-form-note">Sibling order is unavailable because this Category's hierarchy cannot be resolved.</p>
                        @else
                            <p class="category-form-note" data-category-create-order>New Categories are appended to the end of the selected parent's list. Their position can be changed after creation.</p>
                        @endif
                    </section>
                </div>
            </x-admin.card>
        </div>

        <aside class="category-form-aside" data-screen-region="category-context">
            @if ($editing)
                <x-admin.card title="Category Summary" data-screen-region="category-summary">
                    <dl class="category-form-facts">
                        <div><dt>Category ID</dt><dd><code data-category-id>{{ $category->id }}</code></dd></div>
                        <div><dt>Category</dt><dd data-category-lifecycle><x-ui.status-badge :label="$category->status->label()" :tone="$category->status->value === 'active' ? 'success' : 'neutral'" /></dd></div>
                        <div><dt>Schema</dt><dd data-category-schema-state><x-ui.status-badge :label="$category->schema_status->label()" :tone="$category->schema_status->value === 'approved' ? 'success' : 'neutral'" /></dd></div>
                        <div><dt>With Schema</dt><dd data-summary="with-schema">{{ $form->summary->withSchema() ? 'Yes' : 'No' }}</dd></div>
                        <div><dt>Sections</dt><dd data-summary="sections">{{ number_format($form->summary->sections) }}</dd></div>
                        <div><dt>Direct Products</dt><dd data-summary="products">{{ number_format($form->summary->products) }}</dd></div>
                        <div><dt>Selected by Sites</dt><dd data-summary="sites">{{ number_format($form->summary->selectedSites) }}</dd></div>
                        <div><dt>Parent</dt><dd data-summary="parent">@if (! $form->hierarchyAvailable)Hierarchy unavailable @elseif ($form->ancestors){{ implode(' / ', array_column($form->ancestors, 'name')) }}@else Root Category @endif</dd></div>
                        <div><dt>Created</dt><dd><time datetime="{{ $category->created_at?->toAtomString() }}">{{ $category->created_at?->format('d M Y, H:i') ?? 'Not recorded' }}</time></dd></div>
                        <div><dt>Last updated</dt><dd><time datetime="{{ $category->updated_at?->toAtomString() }}" data-category-updated>{{ $category->updated_at?->format('d M Y, H:i') ?? 'Not recorded' }}</time></dd></div>
                    </dl>
                    @if ($schemaUrl)<p class="category-form-link"><a href="{{ $schemaUrl }}">View Schema</a></p>@endif
                </x-admin.card>
                <x-admin.card title="Lifecycle" padding="sm" data-screen-region="lifecycle-context">
                    <p class="category-form-note">Status is not edited here. Active is canonical Category availability. Site selection and publication are managed separately.</p>
                    <p class="category-form-link"><a href="{{ route('central.categories.show', $category) }}">Manage lifecycle in Category Detail</a></p>
                </x-admin.card>
                <x-admin.card title="Localized description" padding="sm" data-screen-region="description-context">
                    <p class="category-form-note">Category descriptions are localized and managed separately. Saving this form never creates or changes a translation.</p>
                    @if ($translationUrl)<p class="category-form-link"><a href="{{ $translationUrl }}">Open translation editor</a></p>@endif
                </x-admin.card>
            @else
                <x-admin.card title="New Category" data-screen-region="creation-guidance">
                    <ul class="category-form-guidance">
                        <li>Starts as <strong>Draft</strong> with a Draft schema; saving does not activate or publish it.</li>
                        <li>Is appended to the end of the selected parent, or of the root list.</li>
                        <li>The slug is the canonical URL key and must be unique.</li>
                        <li>Descriptions, schema and Site selection are managed separately after creation.</li>
                    </ul>
                </x-admin.card>
            @endif
        </aside>
    </div>

    @if ($editing)
        <input type="hidden" name="expected_parent_id" value="{{ $scalar('expected_parent_id', $form->parentId) }}">
        <input type="hidden" name="expected_name" value="{{ $scalar('expected_name', $category->name) }}">
        <input type="hidden" name="expected_slug" value="{{ $scalar('expected_slug', $category->slug) }}">
        <input type="hidden" name="current_scope_revision" value="{{ $scalar('current_scope_revision', $form->scopeRevisionFor($form->parentId)) }}">
        <input type="hidden" name="destination_scope_revision" value="{{ $scalar('destination_scope_revision', $form->scopeRevisionFor($form->parentId)) }}" data-category-destination-revision>
    @else
        <input type="hidden" name="scope_revision" value="{{ $scalar('scope_revision', $form->scopeRevisionFor($selectedParent === null ? null : (int) $selectedParent)) }}" data-category-destination-revision>
    @endif
    <script type="application/json" data-category-scope-revisions>@json((object) $form->scopeRevisions)</script>

    <div class="category-form-actions" data-category-form-actions>
        <x-ui.button variant="secondary" :href="$cancelUrl" data-category-form-cancel>Cancel</x-ui.button>
        <x-ui.button type="submit">Save Category</x-ui.button>
    </div>
</x-ui.form.form-state>
