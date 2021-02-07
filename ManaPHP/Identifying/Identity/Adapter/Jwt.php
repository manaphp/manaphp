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
    protected $scope;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['scopedJwt'])) {
            $this->injections['scopedJwt'] = $options['scopedJwt'];
        }

        $this->scope = $options['scope'] ?? $this->configure->id;
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
}