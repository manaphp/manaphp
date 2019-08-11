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
     * @return mixed|false
     */
    public function get($key)
    {
        if (($data = $this->do_get($key)) === false) {
            $this->eventsManager->fireEvent('cache:miss', $this, ['key' => $key]);
            return false;
        }

        $this->eventsManager->fireEvent('cache:hit', $this, ['key' => $key]);

        if ($data[0] !== '{' && $data[0] !== '[') {
            return $data;
        }

        $json = json_parse($data);
        if (count($json) === 1 && key($json) === '_wrapper_') {
            return $json['_wrapper_'];
        } else {
            return $json;
        }
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return void
     */
    abstract public function do_set($key, $value, $ttl);

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return void
     */
    public function set($key, $value, $ttl)
    {
        if ($value === false) {
            throw new InvalidValueException(['`:key` key cache value can not `false` bool value', 'key' => $key]);
        } elseif (is_scalar($value) || $value === null) {
            if (is_string($value) && $value !== '' && $value[0] !== '{' && $value[0] !== '[') {
                $data = $value;
            } else {
                $data = json_stringify(['_wrapper_' => $value]);
            }
        } elseif (is_array($value) && isset($value['_wrapper_'])) {
            $data = json_stringify(['_wrapper_' => $value]);
        } else {
            $data = json_stringify($value);
        }

        if ($data === false) {
            throw new InvalidValueException([
                '`:key` key cache value json_encode failed: `:code` `:message`',
                'key' => $key,
                'code' => json_last_error(),
                'message' => json_last_error_msg()
            ]);
        }

        $this->do_set($key, $data, $ttl);
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
            $this->set($key, $r, $ttl);
        }

        return $r;
    }
}