<?php

return [
    /*
    |--------------------------------------------------------------------------
    | S3 Disk Configuration
    |--------------------------------------------------------------------------
    |
    | The filesystem disk to use for S3 credentials. Set to null to use
    | the explicit credentials below instead.
    |
    */
    'disk' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Explicit S3 Credentials (Optional)
    |--------------------------------------------------------------------------
    |
    | If you prefer not to use a disk, you can set these values directly.
    | These will override the disk configuration if set.
    |
    */
    'bucket' => env('S3_UPLOADER_BUCKET'),
    'region' => env('S3_UPLOADER_REGION'),
    'key' => env('S3_UPLOADER_KEY'),
    'secret' => env('S3_UPLOADER_SECRET'),
    'endpoint' => env('S3_UPLOADER_ENDPOINT'),
    'use_path_style_endpoint' => env('S3_UPLOADER_PATH_STYLE', false),

    /*
    |--------------------------------------------------------------------------
    | Upload Path Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix path for uploaded files in the S3 bucket.
    |
    */
    'path_prefix' => env('S3_UPLOADER_PATH_PREFIX', 'uploads/multipart'),

    /*
    |--------------------------------------------------------------------------
    | Presigned URL Expiration
    |--------------------------------------------------------------------------
    |
    | How long presigned URLs should be valid for.
    |
    */
    'presigned_url_expiration' => env('S3_UPLOADER_URL_EXPIRATION', '+60 minutes'),

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the package routes. Set enabled to false to disable
    | the default routes and define your own.
    |
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'api/s3/multipart',
        'middleware' => ['api'],
    ],
];