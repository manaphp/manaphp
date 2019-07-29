<?php
namespace ManaPHP\Bos;

use ManaPHP\Component;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Exception\UnauthorizedException;
use ManaPHP\Identity\Adapter\Jwt;

class Client extends Component implements ClientInterface
{
    /**
     * @var string
     */
    protected $_endpoint;

    /**
     * @var string
     */
    protected $_admin_key;

    /**
     * @var array
     */
    protected $_access_key;

    /**
     * Client constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['endpoint'])) {
            $this->_endpoint = $options['endpoint'];
        }

        if (isset($options['admin_key'])) {
            $this->_admin_key = $options['admin_key'];
        }

        if (isset($options['access_key'])) {
            $this->_access_key = $options['access_key'];
        }
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function createBucket($params)
    {
        $params['token'] = jwt_encode(['scope' => 'bos.bucket.create'], 60, $this->_admin_key);

        $endpoint = preg_replace('#{bucket}[\-.]*#', '', $this->_endpoint);

        if (str_contains($this->_endpoint, '{bucket}')) {
            $params['base_url'] = str_replace('{bucket}', $params['bucket'], $this->_endpoint);
        }

        $body = rest_post($endpoint . '/api/buckets', $params)['body'];

        $this->logger->info($body, 'bosClient.createBucket');

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
        $token = jwt_encode(['scope' => 'bos.bucket.list'], 60, $this->_admin_key);
        $endpoint = preg_replace('#{bucket}[\-.]*#', '', $this->_endpoint);
        $body = rest_get([$endpoint . '/api/buckets', 'token' => $token])['body'];

        $this->logger->debug($body, 'bosClient.listBuckets');

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        return $body['data'];
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function listObjects($params = [])
    {
        $bucket = $params['bucket'];

        $endpoint = str_replace('{bucket}', $bucket, $this->_endpoint);

        $params[] = $endpoint . '/api/objects';
        $access_key = is_string($this->_access_key) ? $this->_access_key : $this->_access_key[$bucket];
        $params['token'] = jwt_encode(['scope' => 'bos.object.list', 'bucket' => $bucket], 60, $access_key);

        $body = rest_get($params)['body'];

        $this->logger->debug($body, 'bosClient.listObjects');

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        return $body['data']['items'];
    }

    /**
     * @param array $policy
     * @param int   $ttl
     *
     * @return string
     */
    public function createUploadToken($policy, $ttl = 3600)
    {
        $policy['scope'] = 'bos.object.create.request';

        if (!isset($policy['bucket'])) {
            throw new MissingFieldException('bucket name');
        }
        $bucket = $policy['bucket'];

        $jwt = new Jwt(['key' => is_string($this->_access_key) ? $this->_access_key : $this->_access_key[$bucket]]);

        $token = $jwt->encode($policy, $ttl);

        $this->logger->info($token, 'bosClient.createUploadToken');

        return $token;
    }

    /**
     * verify token of create object response
     *
     * @param string $token
     *
     * @return array
     */
    public function getUploadResult($token)
    {
        $jwt = new Jwt();

        $claims = $jwt->decode($token, null, false);

        if (!isset($claims['scope'])) {
            throw new UnauthorizedException('scope is not exists');
        }

        if ($claims['scope'] !== 'bos.object.create.response') {
            throw new UnauthorizedException(['`:scope` scope is not valid', 'scope' => $claims['scope']]);
        }

        if (!isset($claims['bucket'])) {
            throw new UnauthorizedException('bucket is not exists');
        }

        $bucket = $claims['bucket'];

        $this->logger->info($token, 'bosClient.getUploadResult');

        $jwt->verify($token, is_string($this->_access_key) ? $this->_access_key : $this->_access_key[$bucket]);

        return array_except($claims, ['scope', 'iat', 'exp']);
    }

    /**
     * @param array  $params
     * @param string $file
     *
     * @return array
     */
    public function putObject($params, $file)
    {
        $token = $this->createUploadToken($params, 86400);

        $bucket = $params['bucket'];

        $endpoint = str_replace('{bucket}', $bucket, $this->_endpoint);

        $file = $this->alias->resolve($file);

        $curl_file = curl_file_create($file, mime_content_type($file), basename($file));

        $body = $this->httpClient->post($endpoint . '/api/objects', ['token' => $token, 'file' => $curl_file])->getJsonBody();

        if ($body['code'] !== 0) {
            throw new Exception($body['message'], $body['code']);
        }

        if (!isset($body['data']['token'])) {
            throw new MissingFieldException('token');
        }

        return $this->getUploadResult($body['data']['token']);
    }

    /**
     * @param array  $params
     * @param string $file
     *
     * @return array
     */
    public function upload($params, $file)
    {
        return $this->putObject($params, $file);
    }
}