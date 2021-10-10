<?php

namespace ManaPHP\Token;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;

/**
 * @property-read \ManaPHP\Token\JwtInterface      $jwt
 * @property-read \ManaPHP\Security\CryptInterface $crypt
 */
class ScopedJwt extends Component implements ScopedJwtInterface
{
    /**
     * @var array
     */
    protected $keys = [];

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['keys'])) {
            $this->keys = $options['keys'];
        }
    }

    /**
     * @param string $scope
     *
     * @return string
     */
    public function getKey($scope)
    {
        if (($key = $this->keys[$scope] ?? null) === null) {
            $key = $this->keys[$scope] = $this->crypt->getDerivedKey("jwt:$scope");
        }

        return $key;
    }

    /**
     * @param array  $claims
     * @param int    $ttl
     * @param string $scope
     *
     * @return string
     * @throws MisuseException
     */
    public function encode($claims, $ttl, $scope)
    {
        if (isset($claims['scope'])) {
            throw new MisuseException('scope field is exists');
        }

        $claims['scope'] = $scope;

        return $this->jwt->encode($claims, $ttl, $this->getKey($scope));
    }

    /**
     * @param string $token
     * @param string $scope
     * @param bool   $verify
     *
     * @return array
     * @throws ScopeException
     */
    public function decode($token, $scope, $verify = true)
    {
        $claims = $this->jwt->decode($token, false);

        if (!isset($claims['scope'])) {
            throw new ScopeException('scope is not exists');
        }

        if ($claims['scope'] !== $scope) {
            throw new ScopeException(['`%s` is not equal `%s`', $claims['scope'], $scope]);
        }

        if ($verify) {
            $this->verify($token, $scope);
        }

        return $claims;
    }

    /**
     * @param string $token
     * @param string $scope
     *
     * @return void
     */
    public function verify($token, $scope)
    {
        $this->jwt->verify($token, $this->getKey($scope));
    }

    /**
     * @return array
     **/
    public function dump()
    {
        $data = parent::dump();
        $data['keys'] = '***';

        return $data;
    }
}