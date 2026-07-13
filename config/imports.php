<?php

return [
    'artifact_disk' => env('IMPORTS_DISK', 'imports'),
    'artifact_prefix' => env('IMPORT_ARTIFACT_PREFIX', 'imports'),
    'queued_artifact_threshold_bytes' => (int) env('IMPORT_QUEUED_ARTIFACT_THRESHOLD_BYTES', 5 * 1024 * 1024),
    'serialized_php_max_bytes' => (int) env('IMPORT_SERIALIZED_PHP_MAX_BYTES', 50 * 1024 * 1024),
    'serialized_php_max_depth' => (int) env('IMPORT_SERIALIZED_PHP_MAX_DEPTH', 64),
    'duplicate_min_score' => (float) env('IMPORT_DUPLICATE_MIN_SCORE', 0.55),
    'post_processing_chunk_size' => (int) env('IMPORT_POST_PROCESSING_CHUNK_SIZE', 1),
    'media_download_timeout' => (int) env('IMPORT_MEDIA_DOWNLOAD_TIMEOUT', 10),
    'media_download_max_bytes' => (int) env('IMPORT_MEDIA_DOWNLOAD_MAX_BYTES', 10 * 1024 * 1024),
];
