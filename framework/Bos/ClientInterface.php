<?php
declare(strict_types=1);

namespace ManaPHP\Bos;

interface ClientInterface
{
    public function createBucket(string $bucket, ?string $base_url = null): array;

    public function listBuckets(): array;

    public function listObjects(string $bucket, array $filters = []): array;

    public function putObject(string $file, string $bucket, string $key, array $policy = []): array;

    public function getPutObjectUrl(string $bucket, string $key, array $policy = [], int $ttl = 3600): string;

    public function parsePutObjectResponse(string $token): array;
}