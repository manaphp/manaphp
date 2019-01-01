<?php

namespace ManaPHP\Identity\Adapter;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Identity\ExpiredCredentialException;
use ManaPHP\Identity\InvalidCredentialException;
use ManaPHP\Identity\NotBeforeCredentialException;

/**
 * Class Mwt
 * @package ManaPHP\Identity\Adapter
 */
class Mwt extends Token
{
    /**
     * Mwt constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->_alg = isset($options['alg']) ? $options['alg'] : 'md5';

        if (isset($options['key'])) {
            $this->_key = $options['key'];
        }

        if (isset($options['ttl'])) {
            $this->_ttl = $options['ttl'];
        }
    }

    public function setDi($di)
    {
        parent::setDi($di);

        if ($this->_key === null) {
            $this->_key = $this->crypt->getDerivedKey('mwt');
        }
    }

    /**
     * @param array $claims
     * @param int   $ttl
     *
     * @return string
     */
    public function encode($claims, $ttl = null)
    {
        if (!$this->_key) {
            throw new MisuseException('Mwt key is not set');
        }

        $key = is_string($this->_key) ? $this->_key : $this->_key[0];

        $claims['iat'] = time();
        $claims['exp'] = time() + ($ttl ?: $this->_ttl);

        $payload = $this->base64urlEncode(json_encode($claims, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $signature = $this->base64urlEncode(hash_hmac($this->_alg, $payload, $key, true));

        return "$payload.$signature";
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
        if (count($parts) !== 2) {
            throw new InvalidCredentialException(['The MWT `:token` must have one dot', 'token' => $token]);
        }
        list($payload) = $parts;

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
        if (!$keys = $keys ?: $this->_key) {
            throw new MisuseException('Mwt key is not set');
        }

        if (($pos = strrpos($token, '.')) === false) {
            throw new InvalidCredentialException(['`:token` token is not distinguished', 'token' => $token]);
        }

        $data = substr($token, 0, $pos);
        $signature = substr($token, $pos + 1);

        $success = false;
        /** @noinspection ForeachSourceInspection */
        foreach ((array)$keys as $key) {
            if ($this->base64urlEncode(hash_hmac($this->_alg, $data, $key, true)) === $signature) {
                $success = true;
                break;
            }
        }

        if (!$success) {
            throw new InvalidCredentialException(['signature is not corrected: :signature', 'signature' => $signature]);
        }
    }
}