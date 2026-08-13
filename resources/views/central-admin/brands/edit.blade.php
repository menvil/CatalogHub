@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => 'Edit Brand'])

@php
    $statusVariant = $brand->status->color() === 'gray' ? 'neutral' : $brand->status->color();
@endphp

@section('content')
    <div class="space-y-admin-section" data-brand-form-fixture="brand-form-v1" data-brand-form-mode="edit">
        <x-admin.page-header
            screen-id="CA-013"
            title="Edit Brand"
            :description="'Update canonical information for '.$brand->name.'.'"
            :breadcrumbs="[
                ['label' => 'Central Admin', 'url' => route('filament.central.pages.home', absolute: false)],
                ['label' => 'Brands', 'url' => route('central.brands.index', absolute: false)],
                ['label' => $brand->name],
                ['label' => 'Edit'],
            ]"
        />

        <div class="flex flex-wrap items-center gap-2" data-screen-region="status-context">
            <span class="text-sm font-medium text-admin-muted">Current status</span>
            <x-admin.status-badge :label="$brand->status->label()" :variant="$statusVariant" />
        </div>

        <div class="max-w-4xl" data-screen-region="general-form-card">
            @include('central-admin.brands._form', [
                'mode' => 'edit',
                'brand' => $brand,
                'action' => route('central.brands.update', $brand, absolute: false),
                'method' => 'patch',
                'submitLabel' => 'Save changes',
            ])
        </div>
    </div>
@endsection
