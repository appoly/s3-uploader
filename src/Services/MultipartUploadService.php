<?php

namespace Appoly\S3Uploader\Services;

use Aws\S3\S3Client;
use Illuminate\Support\Str;

class MultipartUploadService
{
    private S3Client $s3Client;
    private string $bucket;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->s3Client = $this->createStorageClient();
        $this->bucket = $config['bucket'] ?? config('filesystems.disks.s3.bucket');
    }

    public function initiateMultipartUpload(string $fileName, string $contentType = 'application/octet-stream'): array
    {
        $prefix = $this->config['path_prefix'] ?? 'uploads/multipart';
        $key = $prefix . '/' . Str::random(10) . '-' . $fileName;

        $result = $this->s3Client->createMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'ContentType' => $contentType,
        ]);

        return [
            'upload_id' => $result['UploadId'],
            'file_path' => $key,
        ];
    }

    public function getPresignedUrlForPart(string $uploadId, string $key, int $partNumber): array
    {
        $expiration = $this->config['presigned_url_expiration'] ?? '+60 minutes';

        $command = $this->s3Client->getCommand('uploadPart', [
            'Bucket' => $this->bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
        ]);

        $signedRequest = $this->s3Client->createPresignedRequest($command, $expiration);
        $uri = $signedRequest->getUri();

        return [
            'presigned_url' => (string) $uri,
            'part_number' => $partNumber,
            'headers' => [],
        ];
    }

    public function completeMultipartUpload(string $uploadId, string $key, array $parts): array
    {
        $formattedParts = collect($parts)
            ->map(fn ($part) => [
                'PartNumber' => (int) $part['part_number'],
                'ETag' => $part['etag'],
            ])
            ->sortBy('PartNumber')
            ->values()
            ->all();

        $result = $this->s3Client->completeMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => ['Parts' => $formattedParts],
        ]);

        return [
            'file_path' => $key,
            'location' => $result['Location'] ?? null,
        ];
    }

    public function abortMultipartUpload(string $uploadId, string $key): array
    {
        $this->s3Client->abortMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
        ]);

        return ['message' => 'Multipart upload aborted'];
    }

    private function createStorageClient(): S3Client
    {
        $disk = $this->config['disk'] ?? 's3';

        $clientConfig = [
            'region' => $this->config['region'] ?? config("filesystems.disks.{$disk}.region"),
            'version' => 'latest',
            'credentials' => [
                'key' => $this->config['key'] ?? config("filesystems.disks.{$disk}.key"),
                'secret' => $this->config['secret'] ?? config("filesystems.disks.{$disk}.secret"),
            ],
        ];

        $endpoint = $this->config['endpoint'] ?? config("filesystems.disks.{$disk}.endpoint");
        if ($endpoint) {
            $clientConfig['endpoint'] = $endpoint;
            $clientConfig['use_path_style_endpoint'] = $this->config['use_path_style_endpoint']
                ?? config("filesystems.disks.{$disk}.use_path_style_endpoint", false);
        }

        return new S3Client($clientConfig);
    }
}