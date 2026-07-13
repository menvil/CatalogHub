<?php

return [
    'artifact_disk' => env('IMPORT_ARTIFACT_DISK', 'local'),
    'artifact_prefix' => env('IMPORT_ARTIFACT_PREFIX', 'imports'),
    'duplicate_min_score' => (float) env('IMPORT_DUPLICATE_MIN_SCORE', 0.55),
    'media_download_timeout' => (int) env('IMPORT_MEDIA_DOWNLOAD_TIMEOUT', 10),
    'media_download_max_bytes' => (int) env('IMPORT_MEDIA_DOWNLOAD_MAX_BYTES', 10 * 1024 * 1024),
];
