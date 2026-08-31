@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => 'Edit Brand'])

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Dashboard</a>
    <span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.index', absolute: false) }}" class="font-medium hover:text-admin-text">Brands</a>
    <span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.show', $brand, absolute: false) }}" class="font-medium hover:text-admin-text">{{ $brand->name }}</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page">Edit</span>
@endsection

@section('content')
    @php
        $ownershipModal = session('brand_ownership_modal');
        $ownershipErrors = session('brand_ownership_errors', []);
        $assignOwnerOpen = $ownershipModal === 'assign';
        $createOwnerOpen = $ownershipModal === 'create';
        $currentOwner = $brand->ownership?->organization;
    @endphp
    <div class="space-y-admin-section" data-brand-form-fixture="brand-form-v4" data-brand-form-mode="edit">
        <x-admin.page-header
            screen-id="CA-013"
            :show-screen-id="false"
            title="Edit Brand"
            :description="'Update canonical information for '.$brand->name.'.'"
            :breadcrumbs="[]"
        >
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('central.brands.show', $brand, absolute: false)">Back to Overview</x-ui.button>
            </x-slot:actions>
        </x-admin.page-header>

        <div class="min-w-0" data-screen-region="brand-profile-workspace">
            @include('central-admin.brands._form', [
                'mode' => 'edit',
                'brand' => $brand,
                'action' => route('central.brands.update', $brand, absolute: false),
                'method' => 'patch',
                'submitLabel' => 'Save changes',
                'cancelUrl' => route('central.brands.show', $brand, absolute: false),
            ])
        </div>

        <x-ui.modal id="manage-parent-company-modal" title="Manage Parent Company" :open="$assignOwnerOpen">
            <form id="assign-parent-company-form" method="POST" action="{{ route('central.brands.ownership.assign', $brand, absolute: false) }}" class="space-y-admin-card">
                @csrf
                <input type="hidden" name="_ownership_operation" value="assign">
                <x-ui.form.searchable-select
                    id="parent-company-organization"
                    name="organization_id"
                    label="Organization"
                    :options="$organizationOptions"
                    :selected="old('organization_id', $currentOwner?->getKey())"
                    :error="$assignOwnerOpen ? data_get($ownershipErrors, 'organization_id.0') : null"
                    placeholder="Search Organizations"
                    search-placeholder="Search by Organization name"
                    help="Results are loaded from the canonical Organization directory. Similar names remain distinct records."
                    :remote="route('central.brands.ownership.organizations.search', $brand, absolute: false)"
                    required
                />
            </form>
            <x-slot:footer>
                <div class="flex flex-wrap justify-end gap-admin-field">
                    <x-ui.button variant="secondary" data-admin-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit" form="assign-parent-company-form">{{ $currentOwner === null ? 'Assign Parent Company' : 'Replace Parent Company' }}</x-ui.button>
                </div>
            </x-slot:footer>
        </x-ui.modal>

        <x-ui.modal id="create-parent-company-modal" title="Create Organization" :open="$createOwnerOpen">
            <form id="create-parent-company-form" method="POST" action="{{ route('central.brands.ownership.create', $brand, absolute: false) }}" class="space-y-admin-card">
                @csrf
                <input type="hidden" name="_ownership_operation" value="create">
                <x-ui.form.input
                    id="new-parent-company-name"
                    name="organization_name"
                    label="Organization name"
                    :value="$createOwnerOpen ? old('organization_name') : ''"
                    :error="$createOwnerOpen ? data_get($ownershipErrors, 'organization_name.0') : null"
                    help="Creates a distinct canonical Organization and assigns it to this Brand. Existing same-name Organizations are not merged automatically."
                    maxlength="255"
                    autocomplete="organization"
                    required
                    data-admin-modal-reset-value=""
                />
            </form>
            <x-slot:footer>
                <div class="flex flex-wrap justify-end gap-admin-field">
                    <x-ui.button variant="secondary" data-admin-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit" form="create-parent-company-form">Create and assign</x-ui.button>
                </div>
            </x-slot:footer>
        </x-ui.modal>

        <form id="clear-parent-company-form" method="POST" action="{{ route('central.brands.ownership.clear', $brand, absolute: false) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
        <x-admin.confirmation-modal
            id="clear-parent-company-modal"
            title="Clear Parent Company?"
            message="This removes only the Brand ownership relation. The Organization remains available for other Brands."
            confirm-label="Clear Parent Company"
            confirm-form="clear-parent-company-form"
            variant="danger"
            :open="false"
        >
            @if ($currentOwner !== null)
                <p class="break-words font-semibold">{{ $currentOwner->name }}</p>
            @endif
        </x-admin.confirmation-modal>
    </div>
@endsection
