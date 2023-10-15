<?php
declare(strict_types=1);

namespace ManaPHP\Di;

use JsonSerializable;
use ManaPHP\Di\Attribute\Autowired;

class Config implements ConfigInterface, JsonSerializable
{
    #[Autowired] protected array $config = [];

    public function all(): array
    {
        return $this->config;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
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

    public function jsonSerialize(): array
    {
        return $this->all();
    }
}