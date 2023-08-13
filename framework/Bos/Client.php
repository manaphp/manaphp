<?php
declare(strict_types=1);

namespace ManaPHP\Bos;

use ManaPHP\AliasInterface;
use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Arr;
use ManaPHP\Http\ClientInterface as HttpClientInterface;
use ManaPHP\Rest\ClientInterface as RestClientInterface;

class Client extends Component implements ClientInterface
{
    #[Inject]
    protected AliasInterface $alias;
    #[Inject]
    protected HttpClientInterface $httpClient;
    #[Inject]
    protected RestClientInterface $restClient;

    protected string $endpoint;

    public function __construct(string $endpoint)
    {
        $this->endpoint = rtrim($endpoint, '/');
    }

    public function createBucket(string $bucket, ?string $base_url = null): array
    {
        $params['token'] = jwt_encode([], 300, 'bos.bucket.create');
        $params['bucket'] = $bucket;
        $params['base_url'] = $base_url;

        $endpoint = preg_replace('#{bucket}[\-.]*#', '', $this->endpoint);

        if (str_contains($this->endpoint, '{bucket}')) {
            $params['base_url'] = str_replace('{bucket}', $bucket, $this->endpoint);
        }

        $body = $this->restClient->post($endpoint . '/api/buckets', $params)->body;

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        return $body['data'];
    }

    public function listBuckets(): array
    {
        $token = jwt_encode([], 300, 'bos.bucket.list');
        $endpoint = preg_replace('#{bucket}[\-.]*#', '', $this->endpoint);
        $body = $this->restClient->get([$endpoint . '/api/buckets', 'token' => $token])->body;

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

        $body = $this->restClient->get($filters)->body;

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        return $body['data'];
    }

    public function getPutObjectUrl(string $bucket, string $key, array $policy = [], int $ttl = 3600): string
    {
        if ($key[0] === '/') {
            throw new MisuseException('key can NOT start with /');
        }

        $policy['bucket'] = $bucket;
        $policy['key'] = $key;

        return str_replace('{bucket}', $bucket, $this->endpoint) . '/api/objects?token='
            . jwt_encode($policy, $ttl, 'bos.object.create.request');
    }

    public function putObject(string $file, string $bucket, string $key, array $policy = []): array
    {
        $url = $this->getPutObjectUrl($bucket, $key, $policy);

        $file = $this->alias->resolve($file);

        $curl_file = curl_file_create($file, mime_content_type($file), basename($file));

        $body = $this->httpClient->post($url, ['file' => $curl_file])->body;

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        if (!isset($body['data']['token'])) {
            throw new MissingFieldException('token');
        }

        return $this->parsePutObjectResponse($body['data']['token']);
    }

    public function parsePutObjectResponse(string $token): array
    {
        $claims = jwt_decode($token, 'bos.object.create.response');

        return Arr::except($claims, ['scope', 'iat', 'exp']);
    }
}