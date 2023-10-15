<?php
declare(strict_types=1);

namespace ManaPHP\Identifying\Identity\Adapter;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\Identity;
use ManaPHP\Token\ScopedJwtInterface;

class Jwt extends Identity
{
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ScopedJwtInterface $scopedJwt;

    #[Autowired] protected ?string $scope;
    #[Autowired] protected int $ttl = 86400;

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