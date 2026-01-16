<?php

namespace Appoly\S3Uploader\Http\Controllers;

use Appoly\S3Uploader\Services\MultipartUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MultipartUploadController extends Controller
{
    public function __construct(
        private MultipartUploadService $uploadService
    ) {}

    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'file_name' => 'required|string',
            'content_type' => 'nullable|string',
        ]);

        $result = $this->uploadService->initiateMultipartUpload(
            $request->input('file_name'),
            $request->input('content_type', 'application/octet-stream')
        );

        return response()->json($result);
    }

    public function presignPart(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => 'required|string',
            'file_path' => 'required|string',
            'part_number' => 'required|integer|min:1',
        ]);

        $result = $this->uploadService->getPresignedUrlForPart(
            $request->input('upload_id'),
            $request->input('file_path'),
            (int) $request->input('part_number')
        );

        return response()->json($result);
    }

    public function complete(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => 'required|string',
            'file_path' => 'required|string',
            'parts' => 'required|array',
            'parts.*.part_number' => 'required|integer',
            'parts.*.etag' => 'required|string',
        ]);

        $result = $this->uploadService->completeMultipartUpload(
            $request->input('upload_id'),
            $request->input('file_path'),
            $request->input('parts')
        );

        return response()->json($result);
    }

    public function abort(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => 'required|string',
            'file_path' => 'required|string',
        ]);

        $result = $this->uploadService->abortMultipartUpload(
            $request->input('upload_id'),
            $request->input('file_path')
        );

        return response()->json($result);
    }
}