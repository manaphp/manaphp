<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Http\GlobalsInterface  $globals
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
class Cookies extends Component implements CookiesInterface
{
    public function all(): array
    {
        return $this->globals->getCookie();
    }

    public function set(string $name, string $value, int $expire = 0, ?string $path = null, ?string $domain = null,
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
}