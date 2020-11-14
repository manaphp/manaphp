<?php

namespace ManaPHP;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Jwt\ExpiredException;
use ManaPHP\Jwt\MalformedException;
use ManaPHP\Jwt\NoCredentialException;
use ManaPHP\Jwt\NotBeforeException;
use ManaPHP\Jwt\ScopeException;
use ManaPHP\Jwt\SignatureException;

/**
 * Class Jwt
 *
 * @package ManaPHP
 */
class Jwt extends Component implements JwtInterface
{
    /**
     * @var string
     */
    protected $_alg = 'HS256';

    /**
     * @var string|array
     */
    protected $_secret;

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

        if (isset($options['secret'])) {
            $this->_secret = $options['secret'];
        } else {
            $this->_secret = $this->getScopedSecret($this->configure->id);
        }
    }

    /**
     * @param string $scope
     *
     * @return string
     */
    public function getScopedSecret($scope)
    {
        if ($scope === '') {
            return $this->crypt->getDerivedKey('jwt');
        } else {
            return $this->crypt->getDerivedKey("jwt:$scope");
        }
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function base64UrlEncode($str)
    {
        return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
    }

    /**
     * @param string $str
     *
     * @return bool|string
     */
    public function base64UrlDecode($str)
    {
        return base64_decode(strtr($str, '-_', '+/'));
    }

    /**
     * @param array  $claims
     * @param int    $ttl
     * @param string $secret
     *
     * @return string
     */
    public function encode($claims, $ttl, $secret = null)
    {
        $claims['iat'] = time();
        $claims['exp'] = time() + $ttl;

        $header = $this->base64UrlEncode(json_stringify(['alg' => $this->_alg, 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_stringify($claims));
        $hmac = hash_hmac(strtr($this->_alg, ['HS' => 'sha']), "$header.$payload", $secret ?? $this->_secret, true);
        $signature = $this->base64UrlEncode($hmac);

        return "$header.$payload.$signature";
    }

    /**
     * @param string       $token
     * @param bool         $verify
     * @param string|array $secrets
     *
     * @return array
     */
    public function decode($token, $verify = true, $secrets = null)
    {
        if ($token === null || $token === '') {
            throw new NoCredentialException('No Credentials');
        }

        $parts = explode('.', $token, 5);
        if (count($parts) !== 3) {
            throw new MalformedException('The JWT must have three dots');
        }

        list($header, $payload) = $parts;

        //DO NOT use json_parse, it maybe generates a lot of Exceptions
        if (!is_array($claims = json_decode($this->base64UrlDecode($payload), true))) {
            throw new MalformedException('payload is not array.');
        }

        //DO NOT use json_parse, it maybe generates a lot of Exceptions
        $decoded_header = json_decode($this->base64UrlDecode($header), true);
        if (!$decoded_header) {
            throw new MalformedException('The JWT header is not distinguished');
        }

        if (!isset($decoded_header['alg'])) {
            throw new MalformedException('The JWT alg field is missing');
        }

        if ($decoded_header['alg'] !== $this->_alg) {
            $decoded_alg = $decoded_header['alg'];
            throw new MalformedException(['The JWT alg `%s` is not same as %s', $decoded_alg, $this->_alg]);
        }

        if (!$decoded_header['typ']) {
            throw new MalformedException(['The JWT typ field is missing: `:token`', 'token' => $token]);
        }

        if ($decoded_header['typ'] !== 'JWT') {
            throw new MalformedException(['The JWT typ `:typ` is not JWT', 'typ' => $decoded_header['typ']]);
        }

        if (isset($claims['exp']) && time() > $claims['exp']) {
            throw new ExpiredException('token is expired.');
        }

        if (isset($claims['nbf']) && time() < $claims['nbf']) {
            throw new NotBeforeException('token is not active.');
        }

        if ($verify) {
            $this->verify($token, $secrets);
        }

        return $claims;
    }

    /**
     * @param string       $token
     * @param string|array $secrets
     */
    public function verify($token, $secrets = null)
    {
        if (($pos = strrpos($token, '.')) === false) {
            throw new MalformedException('The JWT must have three dots');
        }

        $data = substr($token, 0, $pos);
        $signature = substr($token, $pos + 1);

        $success = false;
        foreach ((array)($secrets ?? $this->_secret) as $secret) {
            $hmac = hash_hmac(strtr($this->_alg, ['HS' => 'sha']), $data, $secret, true);
            if ($this->base64UrlEncode($hmac) === $signature) {
                $success = true;
                break;
            }
        }

        if (!$success) {
            throw new SignatureException('signature is not corrected');
        }
    }

    public function scopedEncode($claims, $ttl, $scope)
    {
        if (isset($claims['scope'])) {
            throw new MisuseException('scope field is exists');
        }

        $claims['scope'] = $scope;

        return $this->encode($claims, $ttl, $this->getScopedSecret($scope));
    }

    public function scopedDecode($token, $scope, $verify = true)
    {
        $claims = $this->decode($token, false);

        if (!isset($claims['scope'])) {
            throw new ScopeException('scope is not exists');
        }

        if ($claims['scope'] !== $scope) {
            throw new ScopeException(['`%s` is not equal `%s`', $claims['scope'], $scope]);
        }

        if ($verify) {
            $this->scopedVerify($token, $scope);
        }

        return $claims;
    }

    public function scopedVerify($token, $scope)
    {
        $this->verify($token, $this->getScopedSecret($scope));
    }
}