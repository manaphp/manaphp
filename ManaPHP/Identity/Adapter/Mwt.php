<?php

namespace ManaPHP\Identity\Adapter;

use ManaPHP\Exception\ExpiredCredentialException;
use ManaPHP\Exception\NoCredentialException;
use ManaPHP\Identity;

/**
 * Class Mwt
 * @package ManaPHP\Identity\Adapter
 * @property \ManaPHP\Http\RequestInterface $request
 */
class Mwt extends Identity
{
    /**
     * @var string
     */
    protected $_alg;
    /**
     * @var array
     */
    protected $_key = [];

    /**
     * @var int
     */
    protected $_ttl = 86400;

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
        $signature = $this->base64urlEncode(hash_hmac($this->_alg, $payload, $this->_key[0], true));

        return "$payload.$signature";
    }

    /**
     * @param string $token
     *
     * @return array|false
     */
    public function decode($token)
    {
        $this->_claims = null;

        $parts = explode('.', $token, 5);
        if (count($parts) !== 2) {
            $this->logger->debug(['The MWT `:token` must have one dot', 'token' => $token]);
            return false;
        }
        list($payload, $signature) = $parts;

        $success = false;
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_key as $key) {
            if ($this->base64urlEncode(hash_hmac($this->_alg, $payload, $key, true)) === $signature) {
                $success = true;
                break;
            }
        }

        if (!$success) {
            $this->logger->debug(['signature is not corrected: :signature', 'signature' => $signature]);
            return false;
        }

        $claims = json_decode($this->base64urlDecode($payload), true);
        if (!is_array($claims)) {
            $this->logger->debug('payload is not array.');
            return false;
        }

        if (isset($claims['exp']) && time() > $claims['exp']) {
            throw new ExpiredCredentialException('token is expired.');
        }

        if (isset($claims['nbf']) && time() < $claims['nbf']) {
            $this->logger->debug('token is not active.');
            return false;
        }

        return $claims;
    }

    /**
     * @param bool $silent
     *
     * @return static
     */
    public function authenticate($silent = true)
    {
        $token = $this->request->getAccessToken();
        if (!$token && !$silent) {
            throw new NoCredentialException('no token');
        }
        $claims = $this->decode($token);
        return $this->setClaims($claims ?: []);
    }
}