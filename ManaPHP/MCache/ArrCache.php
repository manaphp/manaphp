<?php

namespace ManaPHP\MCache;

use ManaPHP\MCacheInterface;

class ArrCache implements MCacheInterface
{
    /**
     * @var int
     */
    protected $_ttl;

    /**
     * @var int
     */
    protected $_last_time;

    /**
     * @var array
     */
    protected $_cache_1 = [];

    /**
     * @var array
     */
    protected $_cache_m = [];

    /**
     * SMCache constructor.
     *
     * @param int    $ttl
     * @param string $prefix
     */
    public function __construct($ttl = 1, $prefix = null)
    {
        $this->_ttl = $ttl;
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

        $time = time();

        if ($ttl === 1) {
            if ($this->_last_time !== $time) {
                $this->_last_time = $time;
                $this->_cache_1 = [];
            }

            if (($value = $this->_cache_1[$key] ?? null) === null) {
                $value = $callback($key);
                $this->_cache_1[$key] = $value;
            }
        } else {
            for ($i = $time; $i < $time + $ttl; $i++) {
                if (($value = $this->_cache_m[$i][$key] ?? null) !== null) {
                    return $value;
                }
            }

            if (count($this->_cache_m) > $ttl) {
                foreach ($this->_cache_m as $k => $v) {
                    if ($k < $time) {
                        unset($this->_cache_m[$k]);
                    }
                }
            }

            $value = $callback($key);
            $this->_cache_m[$time + $ttl][$key] = $value;
        }

        return $value;
    }
}