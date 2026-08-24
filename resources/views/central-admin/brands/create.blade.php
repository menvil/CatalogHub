@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => 'Create Brand'])

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Dashboard</a>
    <span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.index', absolute: false) }}" class="font-medium hover:text-admin-text">Brands</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page">Create</span>
@endsection

@section('content')
    <div class="space-y-admin-section" data-brand-form-fixture="brand-form-v1" data-brand-form-mode="create">
        <x-admin.page-header
            screen-id="CA-013"
            title="Create Brand"
            description="Create a canonical brand in the central catalog."
            :breadcrumbs="[]"
        />

        <div class="max-w-4xl" data-screen-region="general-form-card">
            @include('central-admin.brands._form', [
                'mode' => 'create',
                'brand' => null,
                'action' => route('central.brands.store', absolute: false),
                'method' => 'post',
                'submitLabel' => 'Create Brand',
                'cancelUrl' => route('central.brands.index', absolute: false),
            ])
        </div>
    </div>
@endsection
