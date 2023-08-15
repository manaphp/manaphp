<?php
declare(strict_types=1);

namespace ManaPHP\Identifying\Identity\Adapter;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\Identity;
use ManaPHP\Token\ScopedJwtInterface;

class Jwt extends Identity
{
    #[Inject] protected ConfigInterface $config;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ScopedJwtInterface $scopedJwt;

    #[Value] protected ?string $scope;
    #[Value] protected int $ttl = 86400;

    public function authenticate(): array
    {
        if ($token = $this->request->getToken()) {
            return $this->scopedJwt->decode($token, $this->scope ?? $this->config->get('id'));
        } else {
            return [];
        }
    }

    public function encode(array $claims): string
    {
        return $this->scopedJwt->encode($claims, $this->ttl, $this->scope ?? $this->config->get('id'));
    }
}