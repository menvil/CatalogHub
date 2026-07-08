<?php

return [
    'disk' => env('MEDIA_DISK', 'media'),
    'url_disk' => env('MEDIA_URL_DISK', env('PUBLIC_MEDIA_DISK', 'public_media')),
    'path_prefix' => env('MEDIA_PATH_PREFIX', ''),
];
