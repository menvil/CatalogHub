@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => 'Create Brand'])

@section('content')
    <div class="space-y-admin-section" data-brand-form-fixture="brand-form-v1" data-brand-form-mode="create">
        <x-admin.page-header
            screen-id="CA-013"
            title="Create Brand"
            description="Create a canonical brand in the central catalog."
            :breadcrumbs="[
                ['label' => 'Central Admin', 'url' => route('filament.central.pages.home', absolute: false)],
                ['label' => 'Brands', 'url' => route('central.brands.index', absolute: false)],
                ['label' => 'Create'],
            ]"
        />

        <div class="max-w-4xl" data-screen-region="general-form-card">
            @include('central-admin.brands._form', [
                'mode' => 'create',
                'brand' => null,
                'action' => route('central.brands.store', absolute: false),
                'method' => 'post',
                'submitLabel' => 'Create Brand',
            ])
        </div>
    </div>
@endsection
