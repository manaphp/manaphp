<?php

namespace ManaPHP\Cache\Adapter;

use ManaPHP\Cache;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class MemoryContext
{
    /**
     * @var array
     */
    public $data = [];
}

/**
 * Class ManaPHP\Cache\Adapter\Memory
 *
 * @package cache\adapter
 * @property-read \ManaPHP\Cache\Adapter\MemoryContext $_context
 */
class Memory extends Cache
{
    /**
     * @param string $key
     *
     * @return string|false
     */
    public function do_get($key)
    {
        $context = $this->_context;

        if (isset($context->data[$key])) {
            if ($context->data[$key]['deadline'] >= time()) {
                return $context->data[$key]['data'];
            } else {
                unset($context->data[$key]);

                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @return void
     */
    public function do_set($key, $value, $ttl)
    {
        $context = $this->_context;

        $context->data[$key] = ['deadline' => time() + $ttl, 'data' => $value];
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function do_delete($key)
    {
        $context = $this->_context;

        unset($context->data[$key]);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function do_exists($key)
    {
        $context = $this->_context;

        return isset($context->data[$key]) && $context->data[$key]['deadline'] >= time();
    }
}