<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Exception\InvalidArgumentException;

/**
 * @property-read \ManaPHP\Data\RedisDbInterface $redisDb
 */
class Settings extends Component implements SettingsInterface
{
    protected string $key = 'settings';
    protected int $ttl = 1;

    public function __construct(array $options = [])
    {
        if (isset($options['key'])) {
            $this->key = $options['key'];
        }

        if (isset($options['ttl'])) {
            $this->ttl = (int)$options['ttl'];
        }
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
            return apcu_remember(
                $this->key . ':' . $key, $this->ttl, function () use ($default, $key) {
                return $this->getInternal($key, $default);
            }
            );
        }
    }

    public function mGet(array $keys): array
    {
        $values = $this->redisDb->hMGet($this->key, $keys);

        foreach ($keys as $key) {
            if (!isset($values[$key])) {
                throw new InvalidArgumentException(['`%s` key is not exists', $key]);
            }
        }

        return $values;
    }

    public function set(string $key, string $value): static
    {
        $this->redisDb->hSet($this->key, $key, $value);

        return $this;
    }

    public function mSet(array $kvs): static
    {
        $this->redisDb->hMSet($this->key, $kvs);

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
