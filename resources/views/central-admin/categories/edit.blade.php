@extends('layouts.central-admin', ['activeNav' => 'categories', 'pageTitle' => 'Edit '.$form->category->name])

@section('breadcrumbs')
    <a href="{{ route('central.categories.index') }}">Categories</a>
    @foreach ($form->ancestors as $ancestor)
        <span aria-hidden="true">/</span><a href="{{ route('central.categories.show', $ancestor['id']) }}">{{ $ancestor['name'] }}</a>
    @endforeach
    <span aria-hidden="true">/</span><a href="{{ route('central.categories.show', $form->category) }}">{{ $form->category->name }}</a>
    <span aria-hidden="true">/</span><span aria-current="page">Edit</span>
@endsection

@section('content')
<div class="category-form-page" data-screen-id="CA-018" data-category-form-mode="edit" data-fixture-version="category-create-edit-v1">
    <header class="category-form-heading">
        <div class="min-w-0">
            <h1 class="text-foundation-heading font-semibold text-admin-text">Edit Category</h1>
            <p>Update the canonical source name, slug and hierarchy placement of <strong>{{ $form->category->name }}</strong>. Category ID {{ $form->category->id }} stays stable.</p>
        </div>
        <div class="category-form-heading-actions" data-page-actions>
            <x-ui.button variant="secondary" :href="route('central.categories.show', $form->category)" data-category-form-back>Back to Category Detail</x-ui.button>
        </div>
    </header>

    @include('central-admin.categories._form', [
        'mode' => 'edit',
        'action' => route('central.categories.update', $form->category),
        'method' => 'patch',
        'cancelUrl' => route('central.categories.show', $form->category),
    ])
</div>
@endsection
