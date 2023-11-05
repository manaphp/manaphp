<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use JsonSerializable;
use ManaPHP\Di\Attribute\Autowired;

class Cookies implements CookiesInterface, JsonSerializable
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;

    public function all(): array
    {
        return $this->request->getContext()->_COOKIE;
    }

    public function set(string $name, string $value, int $expire = 0, string $path = '', string $domain = '',
        bool $secure = false, bool $httponly = true
    ): static {
        $this->request->getContext()->_COOKIE[$name] = $value;
        $this->response->setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);

        return $this;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->all()[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        return isset($this->all()[$name]);
    }

    public function delete(string $name, ?string $path = null, ?string $domain = null): static
    {
        unset($this->request->getContext()->_COOKIE[$name]);

        $this->response->setCookie(
            $name, 'deleted', 1,
            $path ?? $this->request->path(), $domain ?? $this->request->header('host')
        );

        return $this;
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }
}