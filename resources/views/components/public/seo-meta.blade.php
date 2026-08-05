@props(['metadata'])

<title>{{ $metadata->title }}</title>
@if ($metadata->description !== null)
    <meta name="description" content="{{ $metadata->description }}">
@endif
<link rel="canonical" href="{{ $metadata->canonical }}">
@foreach ($metadata->alternates as $locale => $url)
    <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}">
@endforeach
