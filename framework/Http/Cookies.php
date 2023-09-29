<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use JsonSerializable;
use ManaPHP\Di\Attribute\Autowired;

class Cookies implements CookiesInterface, JsonSerializable
{
    #[Autowired] protected GlobalsInterface $globals;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;

    public function all(): array
    {
        return $this->globals->getCookie();
    }

    public function set(string $name, string $value, int $expire = 0, string $path = '', string $domain = '',
        bool $secure = false, bool $httponly = true
    ): static {
        $this->globals->setCookie($name, $value);
        $this->response->setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);

        return $this;
    }

    public function get(string $name, string $default = ''): string
    {
        return $this->globals->getCookie()[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        return isset($this->globals->getCookie()[$name]);
    }

    public function delete(string $name, ?string $path = null, ?string $domain = null): static
    {
        $this->globals->unsetCookie($name);
        $this->response->setCookie(
            $name, 'deleted', 1,
            $path ?? $this->request->getUri(), $domain ?? $this->request->getHost()
        );

        return $this;
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }
}