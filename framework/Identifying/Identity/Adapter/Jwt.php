<?php
declare(strict_types=1);

namespace ManaPHP\Identifying\Identity\Adapter;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\Identity;
use ManaPHP\Token\ScopedJwtInterface;

class Jwt extends Identity
{
    #[Inject]
    protected ConfigInterface $config;
    #[Inject]
    protected RequestInterface $request;
    #[Inject]
    protected ScopedJwtInterface $scopedJwt;

    protected string $scope;
    protected int $ttl;

    public function __construct(?string $scope = null, int $ttl = 86400)
    {
        $this->scope = $scope ?? $this->config->get('id');
        $this->ttl = $ttl;
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