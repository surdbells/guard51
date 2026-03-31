<?php
declare(strict_types=1);
namespace Guard51\Helper;

use Guard51\Service\FileStorageService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;

trait HandlesFileUploads
{
    /**
     * Extract a single uploaded file from the request and store it.
     * Returns the public URL or null if no file was uploaded.
     */
    protected function handleSingleUpload(
        Request $request,
        FileStorageService $storage,
        string $fieldName,
        string $tenantId,
        string $subDir = 'photos'
    ): ?string {
        $files = $request->getUploadedFiles();
        $file = $files[$fieldName] ?? null;

        if ($file === null || $file->getError() !== UPLOAD_ERR_OK) {
            return null;
        }

        $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION) ?: 'jpg';
        $uuid = Uuid::uuid4()->toString();
        $path = "{$tenantId}/{$subDir}/" . date('Y/m') . "/{$uuid}.{$ext}";

        $contents = $file->getStream()->getContents();
        if ($storage->upload($path, $contents)) {
            return $storage->getUrl($path);
        }
        return null;
    }

    /**
     * Extract multiple uploaded files (evidence_0, evidence_1, ...).
     * Returns array of URLs.
     */
    protected function handleMultipleUploads(
        Request $request,
        FileStorageService $storage,
        string $tenantId,
        string $subDir = 'evidence',
        int $maxFiles = 5
    ): array {
        $files = $request->getUploadedFiles();
        $urls = [];

        for ($i = 0; $i < $maxFiles; $i++) {
            $file = $files["evidence_{$i}"] ?? null;
            if ($file === null || $file->getError() !== UPLOAD_ERR_OK) continue;

            $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION) ?: 'jpg';
            $uuid = Uuid::uuid4()->toString();
            $path = "{$tenantId}/{$subDir}/" . date('Y/m') . "/{$uuid}.{$ext}";

            $contents = $file->getStream()->getContents();
            if ($storage->upload($path, $contents)) {
                $urls[] = [
                    'url' => $storage->getUrl($path),
                    'filename' => $file->getClientFilename(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getClientMediaType(),
                ];
            }
        }
        return $urls;
    }
}
