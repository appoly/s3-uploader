<?php

use Appoly\S3Uploader\Http\Controllers\MultipartUploadController;
use Illuminate\Support\Facades\Route;

$config = config('s3-uploader.routes', []);

Route::prefix($config['prefix'] ?? 'api/s3/multipart')
    ->middleware($config['middleware'] ?? ['api'])
    ->group(function () {
        Route::post('/initiate', [MultipartUploadController::class, 'initiate'])
            ->name('s3-uploader.initiate');
        Route::post('/presign-part', [MultipartUploadController::class, 'presignPart'])
            ->name('s3-uploader.presign-part');
        Route::post('/complete', [MultipartUploadController::class, 'complete'])
            ->name('s3-uploader.complete');
        Route::post('/abort', [MultipartUploadController::class, 'abort'])
            ->name('s3-uploader.abort');
    });