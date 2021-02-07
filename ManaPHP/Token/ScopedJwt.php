<?php

namespace ManaPHP\Token;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;

/**
 * @property-read \ManaPHP\Token\JwtInterface $jwt
 */
class ScopedJwt extends Component implements ScopedJwtInterface
{
    /**
     * @var array
     */
    protected $secrets = [];

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['secrets'])) {
            $this->secrets = $options['secrets'];
        }

        if (isset($options['crypt'])) {
            $this->injections['crypt'] = $options['crypt'];
        }

        if (isset($options['jwt'])) {
            $this->injections['jwt'] = $options['jwt'];
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
        if (($secret = $this->secrets[$scope] ?? null) !== null) {
            return $secret;
        }

        $secret = $this->crypt->getDerivedKey($scope === '' ? 'jwt' : "jwt:$scope");

        if ($cache) {
            $this->secrets[$scope] = $secret;
        }

        return $secret;
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

        return $this->jwt->encode($claims, $ttl, $this->getSecret($scope));
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
        $this->jwt->verify($token, $this->getSecret($scope));
    }

    /**
     * @return array
     **/
    public function dump()
    {
        $data = parent::dump();
        $data['secrets'] = '***';

        return $data;
    }
}