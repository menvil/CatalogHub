@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => 'Create Brand'])

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Dashboard</a>
    <span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.index', absolute: false) }}" class="font-medium hover:text-admin-text">Brands</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page">Create</span>
@endsection

@section('content')
    <div class="space-y-admin-section" data-brand-form-fixture="brand-form-v5" data-brand-form-mode="create">
        <x-admin.page-header
            screen-id="CA-013"
            :show-screen-id="false"
            title="Create Brand"
            description="Create a canonical brand in the central catalog."
            :breadcrumbs="[]"
        >
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('central.brands.index', absolute: false)" data-brand-form-cancel>Cancel</x-ui.button>
                <x-ui.button type="submit" form="brand-form">Create Brand</x-ui.button>
            </x-slot:actions>
        </x-admin.page-header>

        <div class="min-w-0" data-screen-region="brand-profile-workspace">
            @include('central-admin.brands._form', [
                'mode' => 'create',
                'brand' => null,
                'action' => route('central.brands.store', absolute: false),
                'method' => 'post',
            ])
        </div>
    </div>
@endsection
