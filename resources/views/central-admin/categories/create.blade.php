@extends('layouts.central-admin', ['activeNav' => 'categories', 'pageTitle' => 'Create Category'])

@section('breadcrumbs')
    <a href="{{ route('central.categories.index') }}">Categories</a>
    <span aria-hidden="true">/</span><span aria-current="page">New Category</span>
@endsection

@section('content')
<div class="category-form-page" data-screen-id="CA-018" data-category-form-mode="create" data-fixture-version="category-create-edit-v1">
    <header class="category-form-heading">
        <div class="min-w-0">
            <h1 class="text-foundation-heading font-semibold text-admin-text">Create Category</h1>
            <p>Add a canonical Category to the shared catalog hierarchy. It starts as Draft and is not published to any Site.</p>
        </div>
        <div class="category-form-heading-actions" data-page-actions>
            <x-ui.button variant="secondary" :href="route('central.categories.index')">Back to Categories</x-ui.button>
        </div>
    </header>

    @include('central-admin.categories._form', [
        'mode' => 'create',
        'action' => route('central.categories.store'),
        'method' => 'post',
        'cancelUrl' => route('central.categories.index'),
        'slugImpact' => null,
        'schemaUrl' => null,
        'translationUrl' => null,
    ])
</div>
@endsection
