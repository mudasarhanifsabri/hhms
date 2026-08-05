<?php

return [
    'media_disk' => env('HHMS_MEDIA_DISK', 'public'),
    's3_prefix' => env('HHMS_S3_PREFIX', 'HHMS'),
    's3_temporary_url_minutes' => env('HHMS_S3_TEMPORARY_URL_MINUTES', 60),
    'logo_path' => null,
    'favicon_path' => null,
];
