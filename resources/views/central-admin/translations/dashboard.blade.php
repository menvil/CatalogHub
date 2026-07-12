@extends('layouts.central-admin', ['activeNav' => 'Translations', 'pageTitle' => 'Translation Dashboard'])

@section('content')
    @include('central-admin.translations.partials.dashboard-content', ['stats' => $stats])
@endsection
