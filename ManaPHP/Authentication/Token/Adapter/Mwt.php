<?php

namespace ManaPHP\Authentication\Token\Adapter;

use ManaPHP\Authentication\Token;

/**
 * Class ManaPHP\Authentication\Token\Adapter\Mwt
 *
 * @package token\adapter
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
        $this->_alg = isset($options['alg']) ? $options['alg'] : 'md5';
        $this->_key = isset($options['key']) ? (array)$options['key'] : [$this->crypt->getDerivedKey('mwt')];

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
        if (!isset($claims['exp'])) {
            $claims['exp'] = time() + $this->_ttl;
        }

        $payload = $this->base64urlEncode(json_encode($claims, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $hash = $this->base64urlEncode(hash_hmac($this->_alg, $payload, $this->_key[0], true));

        return "$payload.$hash";
    }

    /**
     * @param string $str
     *
     * @return array|false
     */
    public function decode($str)
    {
        $this->_claims = null;

        $parts = explode('.', $str, 5);
        if (count($parts) !== 2) {
            $this->logger->debug(['The MWT `:token` must have one dot', 'token' => $str]);
            return false;
        }
        list($payload, $hash) = $parts;

        $success = false;
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_key as $key) {
            if ($this->base64urlEncode(hash_hmac($this->_alg, $payload, $key, true)) === $hash) {
                $success = true;
                break;
            }
        }

        if (!$success) {
            $this->logger->debug(['hash is not corrected: :hash', 'hash' => $hash]);
            return false;
        }

        $claims = json_decode($this->base64urlDecode($payload), true);
        if (!is_array($claims)) {
            $this->logger->debug('payload is not array.');
            return false;
        }

        if (isset($claims['exp']) && time() > $claims['exp']) {
            $this->logger->debug('token is expired.');
            return false;
        }

        return $this->_claims = $claims;
    }

    public function __toString()
    {
        $data = get_object_vars($this);

        if (isset($data['_claims']['exp'])) {
            $data['_claims']['*expired_at*'] = date('Y-m-d H:i:s', $data['_claims']['exp']);
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}