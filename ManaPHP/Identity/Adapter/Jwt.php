<?php

namespace ManaPHP\Identity\Adapter;

use ManaPHP\Identity\ExpiredCredentialException;
use ManaPHP\Identity\InvalidCredentialException;
use ManaPHP\Identity\NotBeforeCredentialException;

/**
 * Class Jwt
 * @package ManaPHP\Identity\Adapter
 */
class Jwt extends Token
{
    /**
     * Jwt constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->_alg = isset($options['alg']) ? $options['alg'] : 'HS256';
        $this->_key = isset($options['key']) ? (array)$options['key'] : [$this->crypt->getDerivedKey('jwt')];

        if (isset($options['ttl'])) {
            $this->_ttl = $options['ttl'];
        }
    }

    /**
     * @param array $claims
     *
     * @return string
     */
    public function encode($claims)
    {
        $claims['iat'] = time();
        $claims['exp'] = time() + $this->_ttl;

        $header = $this->base64urlEncode(json_encode(['alg' => $this->_alg, 'typ' => 'JWT']));
        $payload = $this->base64urlEncode(json_encode($claims, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $signature = $this->base64urlEncode(hash_hmac(strtr($this->_alg, ['HS' => 'sha']), "$header.$payload", $this->_key[0], true));

        return "$header.$payload.$signature";
    }

    /**
     * @param string $token
     * @param bool   $verify
     *
     * @return array
     */
    public function decode($token, $verify = true)
    {
        $parts = explode('.', $token, 5);
        if (count($parts) !== 3) {
            throw new InvalidCredentialException(['The JWT `:token` must have one dot', 'token' => $token]);
        }

        list($header, $payload) = $parts;
        $decoded_header = json_decode($this->base64urlDecode($header), true);
        if (!$decoded_header) {
            throw new InvalidCredentialException(['The JWT header `:header` is not distinguished', 'header' => $header]);
        }

        if (!isset($decoded_header['alg'])) {
            throw new InvalidCredentialException(['The JWT alg field is missing: `:token`', 'token' => $token]);
        }

        if ($decoded_header['alg'] !== $this->_alg) {
            throw new InvalidCredentialException(['The JWT alg `:alg` is not same as configured :alg2', 'alg' => $decoded_header['alg'], 'alg2' => $this->_alg]);
        }

        if (!$decoded_header['typ']) {
            throw new InvalidCredentialException(['The JWT typ field is missing: `:token`', 'token' => $token]);
        }

        if ($decoded_header['typ'] !== 'JWT') {
            throw new InvalidCredentialException(['The JWT typ `:typ` is not JWT', 'typ' => $decoded_header['typ']]);
        }

        if ($verify) {
            $this->verify($token);
        }

        $claims = json_decode($this->base64urlDecode($payload), true);
        if (!is_array($claims)) {
            throw new InvalidCredentialException('payload is not array.');
        }

        if (isset($claims['exp']) && time() > $claims['exp']) {
            throw new ExpiredCredentialException('token is expired.');
        }

        if (isset($claims['nbf']) && time() < $claims['nbf']) {
            throw new NotBeforeCredentialException('token is not active.');
        }

        return $claims;
    }

    /**
     * @param string       $token
     * @param string|array $keys
     */
    public function verify($token, $keys = null)
    {
        $keys = $keys ? (array)$keys : $this->_key;

        if (($pos = strrpos($token, '.')) === false) {
            throw new InvalidCredentialException(['`:token` token is not distinguished', 'token' => $token]);
        }

        $data = substr($token, 0, $pos);
        $signature = substr($token, $pos + 1);

        $success = false;
        /** @noinspection ForeachSourceInspection */
        foreach ($keys as $key) {
            if ($this->base64urlEncode(hash_hmac(strtr($this->_alg, ['HS' => 'sha']), $data, $key, true)) === $signature) {
                $success = true;
                break;
            }
        }

        if (!$success) {
            throw new InvalidCredentialException(['signature is not corrected: :signature', 'signature' => $signature]);
        }
    }
}