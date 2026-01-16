<?php

namespace Appoly\S3Uploader;

use Appoly\S3Uploader\Services\MultipartUploadService;
use Illuminate\Support\ServiceProvider;

class S3UploaderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/s3-uploader.php', 's3-uploader');

        $this->app->singleton(MultipartUploadService::class, function ($app) {
            return new MultipartUploadService(config('s3-uploader'));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/s3-uploader.php' => config_path('s3-uploader.php'),
            ], 's3-uploader-config');
        }

        if (config('s3-uploader.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }
    }
}