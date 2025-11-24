<?php

declare(strict_types=1);

namespace ManaPHP\Bos;

use ManaPHP\Alias\Path;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Arr;
use ManaPHP\Http\ClientInterface as HttpClientInterface;
use function basename;
use function curl_file_create;
use function jwt_decode;
use function jwt_encode;
use function mime_content_type;
use function preg_replace;
use function str_contains;
use function str_replace;
use function str_starts_with;

class Client implements ClientInterface
{
    #[Autowired] protected HttpClientInterface $httpClient;

    #[Autowired] protected string $endpoint;

    public function createBucket(string $bucket, ?string $base_url = null): array
    {
        $params['token'] = jwt_encode([], 300, 'bos.bucket.create');
        $params['bucket'] = $bucket;
        $params['base_url'] = $base_url;

        $endpoint = preg_replace('#{bucket}[\-.]*#', '', $this->endpoint);

        if (str_contains($this->endpoint, '{bucket}')) {
            $params['base_url'] = str_replace('{bucket}', $bucket, $this->endpoint);
        }

        $body = $this->httpClient->post($endpoint . '/api/buckets', $params)->body;

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        return $body['data'];
    }

    public function listBuckets(): array
    {
        $token = jwt_encode([], 300, 'bos.bucket.list');
        $endpoint = preg_replace('#{bucket}[\-.]*#', '', $this->endpoint);
        $body = $this->httpClient->get([$endpoint . '/api/buckets', 'token' => $token])->body;

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        return $body['data'];
    }

    public function listObjects(string $bucket, array $filters = []): array
    {
        if ($bucket === '') {
            return [];
        }

        $endpoint = str_replace('{bucket}', $bucket, $this->endpoint);

        $filters[] = $endpoint . '/api/objects';
        $filters['bucket'] = $bucket;
        $filters['token'] = jwt_encode(['bucket' => $bucket], 300, 'bos.object.list');

        $body = $this->httpClient->get($filters)->body;

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        return $body['data'];
    }

    public function getPutObjectUrl(string $bucket, string $key, array $policy = [], int $ttl = 3600): string
    {
        if (str_starts_with($key, '/')) {
            throw new MisuseException('BOS object key cannot start with "/" character.', ['bucket' => $bucket, 'key' => $key]);
        }

        $policy['bucket'] = $bucket;
        $policy['key'] = $key;

        return str_replace('{bucket}', $bucket, $this->endpoint) . '/api/objects?token='
            . jwt_encode($policy, $ttl, 'bos.object.create.request');
    }

    public function putObject(string|Path $file, string $bucket, string $key, array $policy = []): array
    {
        $url = $this->getPutObjectUrl($bucket, $key, $policy);

        $file = Path::resolve($file);

        $curl_file = curl_file_create($file, mime_content_type($file), basename($file));

        $body = $this->httpClient->post($url, ['file' => $curl_file])->body;

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        if (!isset($body['data']['token'])) {
            throw new MissingFieldException('BOS upload response is missing required "token" field.', ['bucket' => $bucket, 'key' => $key]);
        }

        return $this->parsePutObjectResponse($body['data']['token']);
    }

    public function parsePutObjectResponse(string $token): array
    {
        $claims = jwt_decode($token, 'bos.object.create.response');

        return Arr::except($claims, ['scope', 'iat', 'exp']);
    }
}
