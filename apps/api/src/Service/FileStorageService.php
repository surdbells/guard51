<?php

declare(strict_types=1);

namespace Guard51\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Psr\Log\LoggerInterface;

final class FileStorageService
{
    private Filesystem $filesystem;

    public function __construct(
        private readonly array $config,
        private readonly LoggerInterface $logger,
    ) {
        $adapter = match ($this->config['driver']) {
            's3' => $this->createS3Adapter(),
            default => new LocalFilesystemAdapter($this->config['local_path']),
        };
        $this->filesystem = new Filesystem($adapter);
    }

    public function upload(string $path, string $contents): bool
    {
        try {
            $this->filesystem->write($path, $contents);
            $this->logger->info('File uploaded.', ['path' => $path]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('File upload failed.', ['path' => $path, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function read(string $path): ?string
    {
        try {
            return $this->filesystem->read($path);
        } catch (\Exception $e) {
            $this->logger->error('File read failed.', ['path' => $path, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function delete(string $path): bool
    {
        try {
            $this->filesystem->delete($path);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('File delete failed.', ['path' => $path, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function exists(string $path): bool
    {
        return $this->filesystem->fileExists($path);
    }

    public function getUrl(string $path): string
    {
        if ($this->config['driver'] === 's3') {
            $bucket = $this->config['s3']['bucket'];
            $region = $this->config['s3']['region'];
            return "https://{$bucket}.s3.{$region}.amazonaws.com/{$path}";
        }
        return '/storage/' . ltrim($path, '/');
    }

    private function createS3Adapter(): \League\Flysystem\AwsS3V3\AwsS3V3Adapter
    {
        $client = new \Aws\S3\S3Client([
            'credentials' => [
                'key' => $this->config['s3']['key'],
                'secret' => $this->config['s3']['secret'],
            ],
            'region' => $this->config['s3']['region'],
            'version' => 'latest',
        ]);
        return new \League\Flysystem\AwsS3V3\AwsS3V3Adapter($client, $this->config['s3']['bucket']);
    }
}
