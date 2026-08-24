<?php

return [
    'disk' => env('MEDIA_UPLOAD_DISK', 'public'),
    'placeholder_url' => env('MEDIA_PLACEHOLDER_URL', '/images/media-placeholder.svg'),
    'dispatch_variants_on_upload' => env('MEDIA_DISPATCH_VARIANTS_ON_UPLOAD', false),
    // This applies to new untrusted raster uploads. Legacy assets remain readable.
    'allowed_upload_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    'max_upload_bytes' => (int) env('MEDIA_MAX_UPLOAD_BYTES', 20 * 1024 * 1024),
    'max_upload_width' => (int) env('MEDIA_MAX_UPLOAD_WIDTH', 8000),
    'max_upload_height' => (int) env('MEDIA_MAX_UPLOAD_HEIGHT', 8000),
    'max_upload_pixels' => (int) env('MEDIA_MAX_UPLOAD_PIXELS', 16_000_000),
    'jpeg_quality' => (int) env('MEDIA_JPEG_QUALITY', 90),
    'png_compression' => (int) env('MEDIA_PNG_COMPRESSION', 6),
    'webp_quality' => (int) env('MEDIA_WEBP_QUALITY', 90),
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
        'brand_logo_128' => ['width' => 128, 'height' => 128, 'fit' => 'contain', 'format' => 'webp', 'quality' => 90, 'allow_upscale' => false],
        'brand_logo_256' => ['width' => 256, 'height' => 256, 'fit' => 'contain', 'format' => 'webp', 'quality' => 90, 'allow_upscale' => false],
        'brand_logo_512' => ['width' => 512, 'height' => 512, 'fit' => 'contain', 'format' => 'webp', 'quality' => 90, 'allow_upscale' => false],
    ],
];
