<?php
namespace ManaPHP\Identity\Adapter;

use ManaPHP\Identity;

/**
 * Class Token
 * @package ManaPHP\Db\Adapter
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
abstract class Token extends Identity
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
        return isset($this->_claims['exp']) ? $this->_claims['exp'] : null;
    }

    /**
     * @param string $token
     *
     * @return array
     */
    abstract public function decode($token);

    /**
     * @param bool $silent
     *
     * @return static
     */
    public function authenticate($silent = true)
    {
        $token = $this->request->getAccessToken();
        if (!$token && !$silent) {
            throw new Identity\NoCredentialException('no token');
        }
        $claims = $this->decode($token);
        return $this->setClaims($claims);
    }
}