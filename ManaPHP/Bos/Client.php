<?php
namespace ManaPHP\Bos;

use http\Exception\RuntimeException;
use ManaPHP\Component;
use ManaPHP\Exception\AuthenticationException;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Identity\Adapter\Jwt;

class Client extends Component implements ClientInterface
{
    /**
     * @var string
     */
    protected $_endpoint;

    /**
     * @var array
     */
    protected $_keys;

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

        if (isset($options['keys'])) {
            $this->_keys = $options['keys'];
        }
    }

    /**
     * create token for create object request
     *
     * @param array $policy
     * @param int   $ttl
     *
     * @return string
     */
    public function createToken($policy, $ttl = 3600)
    {
        $policy['scope'] = 'bos.object.create.request';

        if (!isset($policy['bucket'])) {
            throw new MissingFieldException('bucket name');
        }
        $bucket = $policy['bucket'];

        if (!isset($this->_keys[$bucket])) {
            throw new RuntimeException('bucket access key is not config');
        }
        $access_key = $this->_keys[$bucket];

        $jwt = new Jwt(['key' => $access_key]);

        return $jwt->encode($policy, $ttl);
    }

    /**
     * verify token of create object response
     *
     * @param string $token
     *
     * @return array
     */
    public function verifyToken($token)
    {
        $jwt = new Jwt();

        $claims = $jwt->decode($token, false);

        if (!isset($claims['scope'])) {
            throw new AuthenticationException('scope is not exists');
        }

        if ($claims['scope'] !== 'bos.object.create.response') {
            throw new AuthenticationException(['`:scope` scope is not valid', 'scope' => $claims['scope']]);
        }

        if (!isset($claims['bucket'])) {
            throw new AuthenticationException('bucket is not exists');
        }

        $bucket = $claims['bucket'];

        if (!isset($this->_keys[$bucket])) {
            throw new AuthenticationException('bucket access key is not config');
        }

        $access_key = $this->_keys[$bucket];

        $jwt->verify($token, $access_key);

        return $claims;
    }

    /**
     * @param string $file
     * @param string $bucket
     * @param string $key
     *
     * @return array
     */
    public function upload($file, $bucket, $key)
    {
        $policy = [];
        $policy['bucket'] = $bucket;
        $policy['key'] = $key;

        $token = $this->createToken($policy, 86400);

        $response = $this->httpClient->post($this->_endpoint . '/objects', ['token' => $token, 'file' => $file])->getJsonBody();

        if ($response['code'] !== 0) {
            throw new UploadFailedException(['upload `:file` file failed: :message', 'file' => $file, 'message' => $response['message']]);
        }

        if (!isset($response['data']['token'])) {
            throw new MissingFieldException('token');
        }

        return $this->verifyToken($response['data']['token']);
    }
}