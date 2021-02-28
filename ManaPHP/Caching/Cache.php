<?php

namespace ManaPHP\Caching;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;

abstract class Cache extends Component implements CacheInterface
{
    /**
     * @param string $key
     *
     * @return string|false
     */
    abstract public function do_get($key);

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function get($key)
    {
        if (($data = $this->self->do_get($key)) === false) {
            $this->fireEvent('cache:miss', compact('key'));
            return false;
        } else {
            $this->fireEvent('cache:hit', compact('key', 'data'));
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
            $this->self->do_set($key, $value, $ttl);
        }
    }

    /**
     * @param string $key
     *
     * @return void
     */
    abstract public function do_delete($key);

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->self->do_delete($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    abstract public function do_exists($key);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->self->do_exists($key);
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
        $r = $this->self->get($key);
        if ($r === false) {
            $r = $callback();
            $this->self->set($key, json_stringify($r), $ttl);
        } else {
            $r = json_parse($r);
        }

        return $r;
    }
}