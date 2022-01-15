<?php
declare(strict_types=1);

namespace ManaPHP\Caching;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;

abstract class AbstractCache extends Component implements CacheInterface
{
    abstract public function do_get(string $key): false|string;

    public function get(string $key): false|string
    {
        if (($data = $this->do_get($key)) === false) {
            $this->fireEvent('cache:miss', compact('key'));
            return false;
        } else {
            $this->fireEvent('cache:hit', compact('key', 'data'));
            return $data;
        }
    }

    abstract public function do_set(string $key, string $value, int $ttl): void;

    public function set(string $key, string $value, int $ttl): void
    {
        $this->do_set($key, $value, $ttl);
    }

    abstract public function do_delete(string $key): void;

    public function delete(string $key): void
    {
        $this->do_delete($key);
    }

    abstract public function do_exists(string $key): bool;

    public function exists(string $key): bool
    {
        return $this->do_exists($key);
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $r = $this->get($key);
        if ($r === false) {
            $r = $callback();
            $this->set($key, json_stringify($r), $ttl);
        } else {
            $r = json_parse($r);
        }

        return $r;
    }
}