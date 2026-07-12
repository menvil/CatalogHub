<?php

return [
    'artifact_disk' => env('IMPORT_ARTIFACT_DISK', 'local'),
    'artifact_prefix' => env('IMPORT_ARTIFACT_PREFIX', 'imports'),
    'duplicate_min_score' => (float) env('IMPORT_DUPLICATE_MIN_SCORE', 0.55),
];
