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

# Customization
S3_UPLOADER_PATH_PREFIX=uploads/multipart
S3_UPLOADER_URL_EXPIRATION="+60 minutes"
```

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
// Returns: ['presigned_url' => '...', 'part_number' => 1]

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

## 🛣️ API Endpoints

The package registers these routes by default:

| Method | Endpoint | Description |
|:-------|:---------|:------------|
| `POST` | `/api/s3/multipart/initiate` | 🎬 Start a multipart upload |
| `POST` | `/api/s3/multipart/presign-part` | 🔏 Get presigned URL for a part |
| `POST` | `/api/s3/multipart/complete` | ✅ Complete the upload |
| `POST` | `/api/s3/multipart/abort` | ❌ Abort the upload |

### 📥 Request Examples

**Initiate Upload**
```json
{
    "file_name": "video.mp4",
    "content_type": "video/mp4"
}
```

**Presign Part**
```json
{
    "upload_id": "abc123",
    "file_path": "uploads/multipart/xyz-video.mp4",
    "part_number": 1
}
```

**Complete Upload**
```json
{
    "upload_id": "abc123",
    "file_path": "uploads/multipart/xyz-video.mp4",
    "parts": [
        { "part_number": 1, "etag": "\"abc123\"" },
        { "part_number": 2, "etag": "\"def456\"" }
    ]
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

## 🧪 Testing

```bash
composer test
```

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

## 🏢 Credits

- [Appoly](https://github.com/appoly)

---

<p align="center">
    Made with ❤️ by <a href="https://appoly.co.uk">Appoly</a>
</p>
