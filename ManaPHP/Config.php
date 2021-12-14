<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Exception\InvalidKeyException;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class Config extends Component implements ConfigInterface
{
    protected array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function load(string $file = '@config/app.php'): array
    {
        return $this->config = require $this->alias->resolve($file);
    }

    public function get(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

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