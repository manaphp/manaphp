<?php
declare(strict_types=1);

namespace ManaPHP\Identifying\Identity\Adapter;

use ManaPHP\Identifying\Identity;

/**
 * @property-read \ManaPHP\ConfigInterface          $config
 * @property-read \ManaPHP\Http\RequestInterface    $request
 * @property-read \ManaPHP\Token\ScopedJwtInterface $scopedJwt
 */
class Jwt extends Identity
{
    protected string $scope;
    protected int $ttl = 86400;

    public function __construct(array $options = [])
    {
        if (isset($options['scope'])) {
            $this->scope = $options['scope'];
        } else {
            $this->scope = $this->config->get('id');
        }

        if (isset($options['ttl'])) {
            $this->ttl = (int)$options['ttl'];
        }
    }

    public function authenticate(): array
    {
        if ($token = $this->request->getToken()) {
            return $this->scopedJwt->decode($token, $this->scope);
        } else {
            return [];
        }
    }

    public function encode(array $claims): string
    {
        return $this->scopedJwt->encode($claims, $this->ttl, $this->scope);
    }
}