<?php
namespace ManaPHP\Authentication\Token\Adapter;

use ManaPHP\Authentication\Token;

class Jwt extends Token
{
    /**
     * Jwt constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_alg = isset($options['alg']) ? $options['alg'] : 'HS256';
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

        $header = $this->base64urlEncode(json_encode(['alg' => $this->_alg, 'typ' => 'JWT']));
        $payload = $this->base64urlEncode(json_encode($claims, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $hash = $this->base64urlEncode(hash_hmac(strtr($this->_alg, ['HS' => 'sha']), "$header.$payload", $this->_key[0], true));

        return "$header.$payload.$hash";
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
        if (count($parts) !== 3) {
            $this->logger->debug(['The JWT `:token` must have one dot', 'token' => $str]);
            return false;
        }
        list($header, $payload, $hash) = $parts;
        $decoded_header = json_decode($this->base64urlDecode($header), true);
        if (!$decoded_header) {
            $this->logger->debug(['The JWT header `:header` is not distinguished', 'header' => $header]);
            return false;
        }
        if (!isset($decoded_header['alg'])) {
            $this->logger->debug(['The JWT alg field is missing: `:token`', 'token' => $str]);
            return false;
        }

        if ($decoded_header['alg'] !== $this->_alg) {
            $this->logger->debug(['The JWT alg `:alg` is not same as configured :alg2', 'alg' => $decoded_header['alg'], 'alg2' => $this->_alg]);
            return false;
        }

        if (!$decoded_header['typ']) {
            $this->logger->debug(['The JWT typ field is missing: `:token`', 'token' => $str]);
            return false;
        }

        if ($decoded_header['typ'] !== 'JWT') {
            $this->logger->debug(['The JWT typ `:typ` is not JWT', 'typ' => $decoded_header['typ']]);
        }
        $success = false;
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_key as $key) {
            if ($this->base64urlEncode(hash_hmac(strtr($this->_alg, ['HS' => 'sha']), "$header.$payload", $key, true)) === $hash) {
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

        if (isset($claims['nbf']) && time() < $claims['nbf']) {
            $this->logger->debug('token is not active.');
            return false;
        }

        return $this->_claims = $claims;
    }
}