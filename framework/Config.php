<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Exception\InvalidKeyException;

class Config implements ConfigInterface
{
    #[Inject] protected AliasInterface $alias;

    #[Value] protected array $config = [];

    public function load(string $file = '@config/app.php'): array
    {
        return $this->config = require $this->alias->resolve($file);
    }

    public function all(): array
    {
        return $this->config;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->config[$key] ?? $default;

        if ($value === null) {
            throw new InvalidKeyException("invalid key `$key`");
        } else {
            return $value;
        }
    }

    public function set(string $key, mixed $value): static
    {
        $this->config[$key] = $value;

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }
}