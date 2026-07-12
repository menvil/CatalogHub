<?php

return [
    'disk' => env('MEDIA_UPLOAD_DISK', 'public'),
    'placeholder_url' => env('MEDIA_PLACEHOLDER_URL', '/images/media-placeholder.svg'),
    'dispatch_variants_on_upload' => env('MEDIA_DISPATCH_VARIANTS_ON_UPLOAD', false),
    'allowed_upload_mimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'max_upload_width' => (int) env('MEDIA_MAX_UPLOAD_WIDTH', 8000),
    'max_upload_height' => (int) env('MEDIA_MAX_UPLOAD_HEIGHT', 8000),
    'max_upload_pixels' => (int) env('MEDIA_MAX_UPLOAD_PIXELS', 24_000_000),
    'variants' => [
        'thumbnail' => [
            'width' => 160,
            'height' => 160,
            'fit' => 'cover',
            'format' => 'webp',
            'quality' => 82,
        ],
        'card' => [
            'width' => 640,
            'height' => 640,
            'fit' => 'contain',
            'format' => 'webp',
            'quality' => 84,
        ],
        'gallery' => [
            'width' => 1200,
            'height' => 1200,
            'fit' => 'contain',
            'format' => 'webp',
            'quality' => 86,
        ],
        'hero' => [
            'width' => 1600,
            'height' => 900,
            'fit' => 'cover',
            'format' => 'webp',
            'quality' => 86,
        ],
        'og' => [
            'width' => 1200,
            'height' => 630,
            'fit' => 'cover',
            'format' => 'jpg',
            'quality' => 88,
        ],
    ],
];
