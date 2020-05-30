<?php

namespace ManaPHP\Bos;

use ManaPHP\Component;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Arr;

class Client extends Component implements ClientInterface
{
    /**
     * @var string
     */
    protected $_endpoint;

    /**
     * Client constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['endpoint'])) {
            $this->_endpoint = rtrim($options['endpoint'], '/');
        }
    }

    /**
     * @param string $bucket
     * @param string $base_url
     *
     * @return array
     */
    public function createBucket($bucket, $base_url = null)
    {
        $params['token'] = jwt_encode([], 300, 'bos.bucket.create');
        $params['bucket'] = $bucket;
        $params['base_url'] = $base_url;

        $endpoint = preg_replace('#{bucket}[\-.]*#', '', $this->_endpoint);

        if (str_contains($this->_endpoint, '{bucket}')) {
            $params['base_url'] = str_replace('{bucket}', $bucket, $this->_endpoint);
        }

        $body = rest_post($endpoint . '/api/buckets', $params)->body;

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        return $body['data'];
    }

    /**
     * @return array
     */
    public function listBuckets()
    {
        $token = jwt_encode([], 300, 'bos.bucket.list');
        $endpoint = preg_replace('#{bucket}[\-.]*#', '', $this->_endpoint);
        $body = rest_get([$endpoint . '/api/buckets', 'token' => $token])->body;

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        return $body['data'];
    }

    /**
     * @param string $bucket
     * @param array  $filters
     *
     * @return array
     */
    public function listObjects($bucket, $filters = [])
    {
        if ($bucket === '') {
            return [];
        }

        $endpoint = str_replace('{bucket}', $bucket, $this->_endpoint);

        $filters[] = $endpoint . '/api/objects';
        $filters['bucket'] = $bucket;
        $filters['token'] = jwt_encode(['bucket' => $bucket], 300, 'bos.object.list');

        $body = rest_get($filters)->body;

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        return $body['data'];
    }

    /**
     * @param string $bucket
     * @param string $key
     * @param array  $policy
     * @param int    $ttl
     *
     * @return string
     */
    public function getPutObjectUrl($bucket, $key, $policy = [], $ttl = 3600)
    {
        if ($key[0] === '/') {
            throw new MisuseException('key can NOT start with /');
        }

        $policy['bucket'] = $bucket;
        $policy['key'] = $key;

        return str_replace('{bucket}', $bucket, $this->_endpoint) . '/api/objects?token='
            . jwt_encode($policy, $ttl, 'bos.object.create.request');
    }

    /**
     * @param string $file
     * @param string $bucket
     * @param string $key
     * @param array  $policy
     *
     * @return array
     */
    public function putObject($file, $bucket, $key, $policy = [])
    {
        $url = $this->getPutObjectUrl($bucket, $key, $policy, 3600);

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

    /**
     * @param string $token
     *
     * @return array
     */
    public function parsePutObjectResponse($token)
    {
        $claims = jwt_decode($token, 'bos.object.create.response');

        return Arr::except($claims, ['scope', 'iat', 'exp']);
    }
}