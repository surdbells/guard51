<?php
declare(strict_types=1);
namespace Guard51\Module\Upload;

use Guard51\Helper\JsonResponse;
use Guard51\Service\FileStorageService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;

final class FileUploadController
{
    private const MAX_SIZE = 10 * 1024 * 1024; // 10MB
    private const ALLOWED_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/quicktime', 'video/webm',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function __construct(
        private readonly FileStorageService $storage,
    ) {}

    /** POST /api/v1/uploads — Upload one or more files */
    public function upload(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles();
        $tenantId = $request->getAttribute('tenant_id') ?? 'platform';
        $results = [];

        // Handle both single file and array of files
        $files = [];
        foreach ($uploadedFiles as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $f) $files[] = $f;
            } else {
                $files[] = $file;
            }
        }

        if (empty($files)) {
            return JsonResponse::error($response, 'No files uploaded.', 422);
        }

        foreach ($files as $file) {
            /** @var \Psr\Http\Message\UploadedFileInterface $file */
            if ($file->getError() !== UPLOAD_ERR_OK) continue;
            if ($file->getSize() > self::MAX_SIZE) {
                $results[] = ['error' => $file->getClientFilename() . ' exceeds 10MB limit'];
                continue;
            }

            $mime = $file->getClientMediaType();
            if (!in_array($mime, self::ALLOWED_TYPES, true)) {
                $results[] = ['error' => $file->getClientFilename() . ': file type not allowed'];
                continue;
            }

            $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION) ?: 'bin';
            $uuid = Uuid::uuid4()->toString();
            $path = "{$tenantId}/" . date('Y/m') . "/{$uuid}.{$ext}";

            $stream = $file->getStream();
            $contents = $stream->getContents();

            if ($this->storage->upload($path, $contents)) {
                $results[] = [
                    'id' => $uuid,
                    'filename' => $file->getClientFilename(),
                    'path' => $path,
                    'url' => $this->storage->getUrl($path),
                    'size' => $file->getSize(),
                    'mime_type' => $mime,
                ];
            } else {
                $results[] = ['error' => 'Failed to store ' . $file->getClientFilename()];
            }
        }

        return JsonResponse::success($response, ['files' => $results], 201);
    }

    /** GET /api/v1/uploads/{path} — Serve a file */
    public function serve(Request $request, Response $response, array $args): Response
    {
        $path = $args['path'] ?? '';
        if (!$this->storage->exists($path)) {
            return JsonResponse::error($response, 'File not found.', 404);
        }

        $contents = $this->storage->read($path);
        if ($contents === null) {
            return JsonResponse::error($response, 'Cannot read file.', 500);
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $mimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'mp4' => 'video/mp4', 'pdf' => 'application/pdf'];
        $mime = $mimeMap[strtolower($ext)] ?? 'application/octet-stream';

        $response->getBody()->write($contents);
        return $response
            ->withHeader('Content-Type', $mime)
            ->withHeader('Cache-Control', 'public, max-age=31536000');
    }
}
