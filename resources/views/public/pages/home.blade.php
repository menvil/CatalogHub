@extends('public.layouts.app')

@section('title', $site->name)

@section('content')
    <div data-homepage-blocks class="space-y-10" aria-label="Homepage content">
        @foreach ($blocks as $block)
            @include($block['view'], [
                'config' => $block['config'],
                'data' => $block['data'],
            ])
        @endforeach
    </div>
@endsection
