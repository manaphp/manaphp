<?php
declare(strict_types=1);

namespace ManaPHP\Token;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;

/**
 * @property-read \ManaPHP\Token\JwtInterface      $jwt
 * @property-read \ManaPHP\Security\CryptInterface $crypt
 */
class ScopedJwt extends Component implements ScopedJwtInterface
{
    protected array $keys = [];

    public function __construct(array $options = [])
    {
        if (isset($options['keys'])) {
            $this->keys = $options['keys'];
        }
    }

    public function getKey(string $scope): string
    {
        if (($key = $this->keys[$scope] ?? null) === null) {
            $key = $this->keys[$scope] = $this->crypt->getDerivedKey("jwt:$scope");
        }

        return $key;
    }

    public function encode(array $claims, int $ttl, string $scope): string
    {
        if (isset($claims['scope'])) {
            throw new MisuseException('scope field is exists');
        }

        $claims['scope'] = $scope;

        return $this->jwt->encode($claims, $ttl, $this->getKey($scope));
    }

    public function decode(string $token, string $scope, bool $verify = true): array
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

    public function verify(string $token, string $scope): void
    {
        $this->jwt->verify($token, $this->getKey($scope));
    }

    public function dump(): array
    {
        $data = parent::dump();
        $data['keys'] = '***';

        return $data;
    }
}