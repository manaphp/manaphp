<?php

namespace ManaPHP;

use ManaPHP\Exception\InvalidValueException;

/**
 * Class ManaPHP\Cache
 *
 * @package cache
 */
abstract class Cache extends Component implements CacheInterface
{
    abstract public function do_get($key);

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function get($key)
    {
        if (($data = $this->do_get($key)) === false) {
            $this->fireEvent('cache:miss', $key);
            return false;
        } else {
            $this->fireEvent('cache:hit', $key);
            return $data;
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @return void
     */
    abstract public function do_set($key, $value, $ttl);

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @return void
     */
    public function set($key, $value, $ttl)
    {
        if (!is_string($value)) {
            throw new InvalidValueException(['value of `:key` key must be a string', 'key' => $key]);
        } elseif ($value === 'false') {
            throw new InvalidValueException(['value of `:key` key must be NOT `false` string', 'key' => $key]);
        } else {
            $this->do_set($key, $value, $ttl);
        }
    }

    abstract public function do_delete($key);

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->do_delete($key);
    }

    abstract public function do_exists($key);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->do_exists($key);
    }

    /**
     * @param string   $key
     * @param int      $ttl
     * @param callable $callback
     *
     * @return mixed
     */
    public function remember($key, $ttl, $callback)
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