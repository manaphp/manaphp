<?php

namespace ManaPHP\Identity\Adapter;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Identity;
use ManaPHP\Identity\BadCredentialException;
use ManaPHP\Identity\ExpiredCredentialException;
use ManaPHP\Identity\NoCredentialException;
use ManaPHP\Identity\NotBeforeCredentialException;

/**
 * Class Jwt
 *
 * @package ManaPHP\Identity\Adapter
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Jwt extends Identity
{
    /**
     * @var string
     */
    protected $_alg = 'HS256';

    /**
     * @var string|array
     */
    protected $_key;

    /**
     * @var int
     */
    protected $_ttl = 86400;

    /**
     * Jwt constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['alg'])) {
            $this->_alg = $options['alg'];
        }

        $this->_key = $options['key'] ?? $this->crypt->getDerivedKey('jwt');

        if (isset($options['ttl'])) {
            $this->_ttl = $options['ttl'];
        }
    }

    /**
     * @param int $ttl
     *
     * @return static
     */
    public function setTtl($ttl)
    {
        $this->_ttl = $ttl;

        return $this;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->_ttl;
    }

    /**
     * @param string|array $key
     *
     * @return static
     */
    public function setKey($key)
    {
        $this->_key = (array)$key;

        return $this;
    }

    /**
     * @return array
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * @return int|null
     */
    public function getExpiredTime()
    {
        $context = $this->_context;

        return $context->claims['exp'] ?? null;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function base64urlEncode($str)
    {
        return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
    }

    /**
     * @param string $str
     *
     * @return bool|string
     */
    public function base64urlDecode($str)
    {
        return base64_decode(strtr($str, '-_', '+/'));
    }

    /**
     * @param array  $claims
     * @param int    $ttl
     * @param string $scope
     *
     * @return string
     */
    public function encode($claims, $ttl = null, $scope = null)
    {
        if ($scope) {
            $key = $this->crypt->getDerivedKey("jwt:$scope");
        } else {
            if (!$this->_key) {
                throw new MisuseException('Jwt key is not set');
            }

            $key = is_string($this->_key) ? $this->_key : $this->_key[0];
        }

        $claims['iat'] = time();
        $claims['exp'] = time() + ($ttl ?: $this->_ttl);

        $header = $this->base64urlEncode(json_stringify(['alg' => $this->_alg, 'typ' => 'JWT']));
        $payload = $this->base64urlEncode(json_stringify($claims));
        $signature = $this->base64urlEncode(hash_hmac(strtr($this->_alg, ['HS' => 'sha']), "$header.$payload", $key, true));

        return "$header.$payload.$signature";
    }

    /**
     * @param string $token
     * @param string $scope
     * @param bool   $verify
     *
     * @return array
     */
    public function decode($token, $scope = null, $verify = true)
    {
        if ($token === null || $token === '') {
            throw new NoCredentialException('No Credentials');
        }

        $parts = explode('.', $token, 5);
        if (count($parts) !== 3) {
            throw new BadCredentialException(['The JWT `:token` must have one dot', 'token' => $token]);
        }

        list($header, $payload) = $parts;

        //DO NOT use json_parse, it maybe generates a lot of Exceptions
        if (!is_array($claims = json_decode($this->base64urlDecode($payload), true))) {
            throw new BadCredentialException('payload is not array.');
        }

        if (!$verify) {
            return $claims;
        }

        //DO NOT use json_parse, it maybe generates a lot of Exceptions
        $decoded_header = json_decode($this->base64urlDecode($header), true);
        if (!$decoded_header) {
            throw new BadCredentialException(['The JWT header `:header` is not distinguished', 'header' => $header]);
        }

        if (!isset($decoded_header['alg'])) {
            throw new BadCredentialException(['The JWT alg field is missing: `:token`', 'token' => $token]);
        }

        if ($decoded_header['alg'] !== $this->_alg) {
            throw new BadCredentialException(['The JWT alg `:alg` is not same as configured :alg2', 'alg' => $decoded_header['alg'], 'alg2' => $this->_alg]);
        }

        if (!$decoded_header['typ']) {
            throw new BadCredentialException(['The JWT typ field is missing: `:token`', 'token' => $token]);
        }

        if ($decoded_header['typ'] !== 'JWT') {
            throw new BadCredentialException(['The JWT typ `:typ` is not JWT', 'typ' => $decoded_header['typ']]);
        }

        $this->verify($token, $scope);

        if (isset($claims['exp']) && time() > $claims['exp']) {
            throw new ExpiredCredentialException('token is expired.');
        }

        if (isset($claims['nbf']) && time() < $claims['nbf']) {
            throw new NotBeforeCredentialException('token is not active.');
        }

        return $claims;
    }

    /**
     * @param string $token
     * @param string $scope
     */
    public function verify($token, $scope = null)
    {
        if ($scope) {
            $keys = $this->crypt->getDerivedKey("jwt:$scope");
        } else {
            if (!$keys = $this->_key) {
                throw new MisuseException('Jwt key is not set');
            }
        }

        if (($pos = strrpos($token, '.')) === false) {
            throw new BadCredentialException(['`:token` token is not distinguished', 'token' => $token]);
        }

        $data = substr($token, 0, $pos);
        $signature = substr($token, $pos + 1);

        $success = false;
        foreach ((array)$keys as $key) {
            if ($this->base64urlEncode(hash_hmac(strtr($this->_alg, ['HS' => 'sha']), $data, $key, true)) === $signature) {
                $success = true;
                break;
            }
        }

        if (!$success) {
            throw new BadCredentialException(['signature is not corrected: :signature', 'signature' => $signature]);
        }
    }

    /**
     * @param bool $silent
     *
     * @return static
     */
    public function authenticate($silent = true)
    {
        if ($token = $this->request->getToken()) {
            $claims = $this->decode($token);
            return $this->setClaims($claims);
        } elseif ($silent) {
            return $this;
        } else {
            throw new NoCredentialException('no token');
        }
    }
}