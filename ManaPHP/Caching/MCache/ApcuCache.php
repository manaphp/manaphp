<?php

namespace ManaPHP\Caching\MCache;

use ManaPHP\Caching\MCacheInterface;
use ManaPHP\Coroutine;
use ManaPHP\Exception\NotSupportedException;

class ApcuCache implements MCacheInterface
{
    /**
     * @var int
     */
    protected $_ttl;

    /**
     * @var string
     */
    protected $_prefix;

    /**
     * SMCache constructor.
     *
     * @param int    $ttl
     * @param string $prefix
     */
    public function __construct($ttl = 1, $prefix = null)
    {
        $this->_ttl = $ttl;

        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            throw new NotSupportedException('apcu is not enabled');
        }

        if ($prefix === null) {
            $trace = Coroutine::getBacktrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            $prefix = $trace['file'] . ':' . $trace['line'] . ':';
        }

        $this->_prefix = $prefix;
    }

    /**
     * @param string   $key
     * @param callable $callback
     * @param int      $ttl
     *
     * @return mixed
     */
    public function get($key, $callback, $ttl = null)
    {
        $ttl = $ttl ?? $this->_ttl;
        $apcu_key = $this->_prefix . $key;

        $value = apcu_fetch($apcu_key, $success);
        if (!$success) {
            $value = $callback($key);
            apcu_store($apcu_key, $value, $ttl);
        }

        return $value;
    }
}