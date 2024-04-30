<?php
declare(strict_types=1);

namespace ManaPHP\Identifying\Identity\Adapter;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\Identity;
use ManaPHP\Token\ScopedJwtInterface;
use function count;

class Jwt extends Identity
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ScopedJwtInterface $scopedJwt;

    #[Autowired] protected ?string $scope;
    #[Autowired] protected int $ttl = 86400;

    #[Config] protected string $app_id;

    protected function getToken(): ?string
    {
        if (($token = $this->request->header('authorization')) !== null) {
            $parts = explode(' ', $token, 2);
            if ($parts[0] === 'Bearer' && count($parts) === 2) {
                return $parts[1];
            }
        } elseif (($token = $this->request->input('access_token')) !== null) {
            return $token;
        }

        return null;
    }

    public function authenticate(): array
    {
        $token = $this->getToken();
        if ($token !== '' && $token !== null) {
            return $this->scopedJwt->decode($token, $this->scope ?? $this->app_id);
        } else {
            return [];
        }
    }

    public function encode(array $claims): string
    {
        return $this->scopedJwt->encode($claims, $this->ttl, $this->scope ?? $this->app_id);
    }
}