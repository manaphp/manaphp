<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Exception\InvalidArgumentException;

/**
 * @property-read \ManaPHP\Data\RedisDbInterface $redisDb
 */
class Settings extends Component implements SettingsInterface
{
    protected string $key;
    protected int $ttl;

    public function __construct(string $key = 'settings', int $ttl = 1)
    {
        $this->key = $key;
        $this->ttl = $ttl;
    }

    public function getInternal(string $key, ?string $default = null): ?string
    {
        if (($value = $this->redisDb->hGet($this->key, $key)) === false) {
            if ($default === null) {
                throw new InvalidArgumentException(['`%s` key is not exists', $key]);
            } else {
                $value = $default;
            }
        }
        return $value;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        if ($this->ttl <= 0) {
            return $this->getInternal($key, $default);
        } else {
            return apcu_remember($this->key . ':' . $key, $this->ttl, fn() => $this->getInternal($key, $default));
        }
    }

    public function set(string $key, string $value): static
    {
        $this->redisDb->hSet($this->key, $key, $value);

        return $this;
    }

    public function exists(string $key): bool
    {
        return $this->redisDb->hExists($this->key, $key);
    }

    public function delete(string $key): static
    {
        $this->redisDb->hDel($this->key, $key);

        return $this;
    }
}
