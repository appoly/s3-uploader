# 🚀 S3 Uploader for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/appoly/s3-uploader.svg?style=flat-square)](https://packagist.org/packages/appoly/s3-uploader)
[![Total Downloads](https://img.shields.io/packagist/dt/appoly/s3-uploader.svg?style=flat-square)](https://packagist.org/packages/appoly/s3-uploader)
[![License](https://img.shields.io/packagist/l/appoly/s3-uploader.svg?style=flat-square)](https://packagist.org/packages/appoly/s3-uploader)

A Laravel package for handling S3 multipart uploads with presigned URLs. Perfect for uploading large files directly from the browser to S3.

---

## ✨ Features

- 📤 **Multipart uploads** - Upload large files in chunks
- 🔐 **Presigned URLs** - Secure, time-limited upload URLs
- ⚡ **Direct to S3** - Browser uploads directly to S3, bypassing your server
- 🎛️ **Configurable** - Flexible configuration options
- 🛣️ **Optional routes** - Use built-in routes or define your own
- 🏗️ **Facade & DI** - Use however you prefer

---

## 📦 Installation

```bash
composer require appoly/s3-uploader
```

---

## ⚙️ Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=s3-uploader-config
```

By default, the package uses your `s3` filesystem disk configuration. Override in `config/s3-uploader.php` or via environment variables.

### 🔑 Environment Variables

```env
# Optional - defaults to your S3 disk settings
S3_UPLOADER_BUCKET=your-bucket
S3_UPLOADER_REGION=eu-west-2
S3_UPLOADER_KEY=your-key
S3_UPLOADER_SECRET=your-secret
S3_UPLOADER_ENDPOINT=
S3_UPLOADER_PATH_STYLE=false

# Customization
S3_UPLOADER_PATH_PREFIX=uploads/multipart
S3_UPLOADER_URL_EXPIRATION="+60 minutes"
```

| Variable | Default | Description |
|:---------|:--------|:------------|
| `S3_UPLOADER_BUCKET` | S3 disk bucket | S3 bucket name |
| `S3_UPLOADER_REGION` | S3 disk region | AWS region (e.g., `eu-west-2`) |
| `S3_UPLOADER_KEY` | S3 disk key | AWS Access Key ID |
| `S3_UPLOADER_SECRET` | S3 disk secret | AWS Secret Access Key |
| `S3_UPLOADER_ENDPOINT` | S3 disk endpoint | Custom endpoint for S3-compatible services (MinIO, DigitalOcean Spaces, etc.) |
| `S3_UPLOADER_PATH_STYLE` | `false` | Use path-style URLs instead of virtual-hosted style (required for some S3-compatible services) |
| `S3_UPLOADER_PATH_PREFIX` | `uploads/multipart` | Prefix path for uploaded files in S3 |
| `S3_UPLOADER_URL_EXPIRATION` | `+60 minutes` | How long presigned URLs remain valid |

---

## 🚀 Usage

### Using the Facade

```php
use Appoly\S3Uploader\Facades\S3Uploader;

// 1️⃣ Initiate upload
$upload = S3Uploader::initiateMultipartUpload('video.mp4', 'video/mp4');
// Returns: ['upload_id' => '...', 'file_path' => '...']

// 2️⃣ Get presigned URL for each part
$part = S3Uploader::getPresignedUrlForPart(
    $upload['upload_id'],
    $upload['file_path'],
    partNumber: 1
);
// Returns: ['presigned_url' => '...', 'part_number' => 1, 'headers' => []]

// 3️⃣ Complete upload (after all parts uploaded)
$result = S3Uploader::completeMultipartUpload(
    $upload['upload_id'],
    $upload['file_path'],
    $parts // [['part_number' => 1, 'etag' => '...'], ...]
);
// Returns: ['file_path' => '...', 'location' => '...']

// ❌ Abort upload (if needed)
S3Uploader::abortMultipartUpload($upload['upload_id'], $upload['file_path']);
```

### Using Dependency Injection

```php
use Appoly\S3Uploader\Services\MultipartUploadService;

class UploadController
{
    public function __construct(
        private MultipartUploadService $uploader
    ) {}

    public function start()
    {
        return $this->uploader->initiateMultipartUpload('document.pdf', 'application/pdf');
    }
}
```

---

### 🗄️ Config Options

You can also configure the package directly in `config/s3-uploader.php`:

```php
return [
    // Use a specific Laravel filesystem disk for credentials (set to null to use explicit credentials)
    'disk' => 's3',

    // S3 credentials (falls back to disk settings if not specified)
    'bucket' => env('S3_UPLOADER_BUCKET'),
    'region' => env('S3_UPLOADER_REGION'),
    'key' => env('S3_UPLOADER_KEY'),
    'secret' => env('S3_UPLOADER_SECRET'),
    'endpoint' => env('S3_UPLOADER_ENDPOINT'),
    'use_path_style_endpoint' => env('S3_UPLOADER_PATH_STYLE', false),

    // Upload settings
    'path_prefix' => env('S3_UPLOADER_PATH_PREFIX', 'uploads/multipart'),
    'presigned_url_expiration' => env('S3_UPLOADER_URL_EXPIRATION', '+60 minutes'),

    // Route settings
    'routes' => [
        'enabled' => true,
        'prefix' => 'api/s3/multipart',
        'middleware' => ['api'],
    ],
];
```

---

## 🛣️ API Endpoints

The package registers these routes by default (can be customized via `routes.prefix` and `routes.middleware`):

| Method | Endpoint | Route Name | Description |
|:-------|:---------|:-----------|:------------|
| `POST` | `/api/s3/multipart/initiate` | `s3-uploader.initiate` | Start a multipart upload |
| `POST` | `/api/s3/multipart/presign-part` | `s3-uploader.presign-part` | Get presigned URL for a part |
| `POST` | `/api/s3/multipart/complete` | `s3-uploader.complete` | Complete the upload |
| `POST` | `/api/s3/multipart/abort` | `s3-uploader.abort` | Abort the upload |

---

### POST `/api/s3/multipart/initiate`

Start a new multipart upload session.

**Headers:**
| Header | Required | Value |
|:-------|:---------|:------|
| `Content-Type` | Yes | `application/json` |
| `Accept` | Yes | `application/json` |

**Request Body:**
| Parameter | Type | Required | Description |
|:----------|:-----|:---------|:------------|
| `file_name` | string | Yes | Original filename (e.g., `video.mp4`) |
| `content_type` | string | No | MIME type (default: `application/octet-stream`) |

**Example Request:**
```json
{
    "file_name": "video.mp4",
    "content_type": "video/mp4"
}
```

**Example Response:**
```json
{
    "upload_id": "abc123def456...",
    "file_path": "uploads/multipart/550e8400-e29b-41d4-a716-446655440000-video.mp4"
}
```

---

### POST `/api/s3/multipart/presign-part`

Get a presigned URL to upload a specific part directly to S3.

**Headers:**
| Header | Required | Value |
|:-------|:---------|:------|
| `Content-Type` | Yes | `application/json` |
| `Accept` | Yes | `application/json` |

**Request Body:**
| Parameter | Type | Required | Description |
|:----------|:-----|:---------|:------------|
| `upload_id` | string | Yes | Upload ID from initiate response |
| `file_path` | string | Yes | File path from initiate response |
| `part_number` | integer | Yes | Part number (starts at 1) |

**Example Request:**
```json
{
    "upload_id": "abc123def456...",
    "file_path": "uploads/multipart/550e8400-e29b-41d4-a716-446655440000-video.mp4",
    "part_number": 1
}
```

**Example Response:**
```json
{
    "presigned_url": "https://bucket.s3.region.amazonaws.com/...",
    "part_number": 1,
    "headers": {}
}
```

---

### POST `/api/s3/multipart/complete`

Complete the multipart upload after all parts have been uploaded to S3.

**Headers:**
| Header | Required | Value |
|:-------|:---------|:------|
| `Content-Type` | Yes | `application/json` |
| `Accept` | Yes | `application/json` |

**Request Body:**
| Parameter | Type | Required | Description |
|:----------|:-----|:---------|:------------|
| `upload_id` | string | Yes | Upload ID from initiate response |
| `file_path` | string | Yes | File path from initiate response |
| `parts` | array | Yes | Array of uploaded parts |
| `parts.*.part_number` | integer | Yes | Part number |
| `parts.*.etag` | string | Yes | ETag returned by S3 after uploading the part |

**Example Request:**
```json
{
    "upload_id": "abc123def456...",
    "file_path": "uploads/multipart/550e8400-e29b-41d4-a716-446655440000-video.mp4",
    "parts": [
        { "part_number": 1, "etag": "\"a54357aff0632cce46d942af68356b38\"" },
        { "part_number": 2, "etag": "\"0c78aef83f66abc1fa1e8477f296d394\"" }
    ]
}
```

**Example Response:**
```json
{
    "file_path": "uploads/multipart/550e8400-e29b-41d4-a716-446655440000-video.mp4",
    "location": "https://bucket.s3.region.amazonaws.com/uploads/multipart/550e8400-e29b-41d4-a716-446655440000-video.mp4"
}
```

---

### POST `/api/s3/multipart/abort`

Abort an in-progress multipart upload and clean up any uploaded parts.

**Headers:**
| Header | Required | Value |
|:-------|:---------|:------|
| `Content-Type` | Yes | `application/json` |
| `Accept` | Yes | `application/json` |

**Request Body:**
| Parameter | Type | Required | Description |
|:----------|:-----|:---------|:------------|
| `upload_id` | string | Yes | Upload ID from initiate response |
| `file_path` | string | Yes | File path from initiate response |

**Example Request:**
```json
{
    "upload_id": "abc123def456...",
    "file_path": "uploads/multipart/550e8400-e29b-41d4-a716-446655440000-video.mp4"
}
```

**Example Response:**
```json
{
    "success": true
}
```

---

## 🎨 Customizing Routes

Disable default routes and define your own:

```php
// config/s3-uploader.php
'routes' => [
    'enabled' => false,
],
```

Then in your routes file:

```php
use Appoly\S3Uploader\Http\Controllers\MultipartUploadController;

Route::middleware(['auth:sanctum'])->prefix('api/uploads')->group(function () {
    Route::post('/start', [MultipartUploadController::class, 'initiate']);
    Route::post('/sign', [MultipartUploadController::class, 'presignPart']);
    Route::post('/finish', [MultipartUploadController::class, 'complete']);
    Route::post('/cancel', [MultipartUploadController::class, 'abort']);
});
```

---

## 🌐 Frontend Example

Here's a basic JavaScript example for uploading:

```javascript
async function uploadFile(file) {
    const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB minimum for S3
    const totalParts = Math.ceil(file.size / CHUNK_SIZE);

    // 1️⃣ Initiate
    const { upload_id, file_path } = await fetch('/api/s3/multipart/initiate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            file_name: file.name,
            content_type: file.type
        })
    }).then(r => r.json());

    // 2️⃣ Upload parts
    const parts = [];
    for (let partNumber = 1; partNumber <= totalParts; partNumber++) {
        const start = (partNumber - 1) * CHUNK_SIZE;
        const end = Math.min(start + CHUNK_SIZE, file.size);
        const chunk = file.slice(start, end);

        // Get presigned URL
        const { presigned_url } = await fetch('/api/s3/multipart/presign-part', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ upload_id, file_path, part_number: partNumber })
        }).then(r => r.json());

        // Upload chunk directly to S3
        const response = await fetch(presigned_url, { method: 'PUT', body: chunk });
        parts.push({ part_number: partNumber, etag: response.headers.get('ETag') });
    }

    // 3️⃣ Complete
    return fetch('/api/s3/multipart/complete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ upload_id, file_path, parts })
    }).then(r => r.json());
}
```

---

## 🏢 Credits

- [Appoly](https://github.com/appoly)

---

<p align="center">
    Made with ❤️ by <a href="https://appoly.co.uk">Appoly</a>
</p>