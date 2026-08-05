@props(['metadata'])

<title>{{ $metadata->title }}</title>
@if (filled($metadata->description))
    <meta name="description" content="{{ $metadata->description }}">
@endif
<link rel="canonical" href="{{ $metadata->canonical }}">
@foreach ($metadata->alternates as $locale => $url)
    <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}">
@endforeach
