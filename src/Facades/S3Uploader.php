<?php

namespace Appoly\S3Uploader\Facades;

use Appoly\S3Uploader\Services\MultipartUploadService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array initiateMultipartUpload(string $fileName, string $contentType = 'application/octet-stream')
 * @method static array getPresignedUrlForPart(string $uploadId, string $key, int $partNumber)
 * @method static array completeMultipartUpload(string $uploadId, string $key, array $parts)
 * @method static array abortMultipartUpload(string $uploadId, string $key)
 *
 * @see \Appoly\S3Uploader\Services\MultipartUploadService
 */
class S3Uploader extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MultipartUploadService::class;
    }
}