<?php

namespace ManaPHP\Identifying\Identity\Adapter;

use ManaPHP\Identifying\Identity;

/**
 * @property-read \ManaPHP\Http\RequestInterface    $request
 * @property-read \ManaPHP\Token\ScopedJwtInterface $scopedJwt
 */
class Jwt extends Identity
{
    /**
     * @var string
     */
    protected $scope = APP_ID;

    /**
     * @var int
     */
    protected $ttl = 86400;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['scope'])) {
            $this->scope = $options['scope'];
        }

        if (isset($options['ttl'])) {
            $this->ttl = (int)$options['ttl'];
        }
    }

    /**
     * @return array
     */
    public function authenticate()
    {
        if ($token = $this->request->getToken()) {
            return $this->scopedJwt->decode($token, $this->scope);
        } else {
            return [];
        }
    }

    /**
     * @param array $claims
     *
     * @return string
     */
    public function encode($claims)
    {
        return $this->scopedJwt->encode($claims, $this->ttl, $this->scope);
    }
}