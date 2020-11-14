<?php

namespace ManaPHP\Token;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;

/**
 * Class ScopedJwt
 *
 * @package ManaPHP\Token\Jwt
 * @property-read \ManaPHP\Token\JwtInterface $jwt
 */
class ScopedJwt extends Component implements ScopedJwtInterface
{
    /**
     * @var array
     */
    protected $_secrets = [];

    /**
     * ScopedJwt constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['secrets'])) {
            $this->_secrets = $options['secrets'];
        }
    }

    /**
     * @param string $scope
     * @param bool   $cache
     *
     * @return string
     */
    public function getSecret($scope, $cache = true)
    {
        if (($secret = $this->_secrets[$scope] ?? null) !== null) {
            return $secret;
        }

        $secret = $this->crypt->getDerivedKey($scope === '' ? 'jwt' : "jwt:$scope");

        if ($cache) {
            $this->_secrets[$scope] = $secret;
        }

        return $secret;
    }

    public function encode($claims, $ttl, $scope)
    {
        if (isset($claims['scope'])) {
            throw new MisuseException('scope field is exists');
        }

        $claims['scope'] = $scope;

        return $this->jwt->encode($claims, $ttl, $this->getSecret($scope));
    }

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

    public function verify($token, $scope)
    {
        $this->jwt->verify($token, $this->getSecret($scope));
    }
}