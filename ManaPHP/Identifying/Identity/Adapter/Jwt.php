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
    protected $_scope;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['scopedJwt'])) {
            $this->_injections['scopedJwt'] = $options['scopedJwt'];
        }

        $this->_scope = $options['scope'] ?? $this->configure->id;
    }

    /**
     * @return array
     */
    public function authenticate()
    {
        if ($token = $this->request->getToken()) {
            return $this->scopedJwt->decode($token, $this->_scope);
        } else {
            return [];
        }
    }
}