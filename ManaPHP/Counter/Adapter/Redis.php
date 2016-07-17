<?php
namespace ManaPHP\Counter\Adapter;

use ManaPHP\Counter;

class Redis extends Counter
{
    protected $_prefix;

    /**
     * Redis constructor.
     *
     * @param string $prefix
     */
    public function __construct($prefix = 'manaphp:counter:')
    {
        parent::__construct();

        $this->_prefix = $prefix;
    }

    /**
     * @param string|array $key
     *
     * @return array
     */
    protected function _getKey($key)
    {
        if (is_string($key)) {
            $r = [$this->_prefix . 'mixed', $key];
        } else {
            $r = [$this->_prefix . $key[0], $key[1]];
        }

        return $r;
    }

    /**
     * @param array|string $key
     *
     * @return int
     */
    public function _get($key)
    {
        $key = $this->_getKey($key);

        return (int)$this->redis->hGet($key[0], $key[1]);
    }

    /**
     * @param array|string $key
     * @param int          $step
     *
     * @return int
     */
    public function _increment($key, $step)
    {
        $key = $this->_getKey($key);

        return $this->redis->hIncrBy($key[0], $key[1], $step);
    }

    /**
     * @param array|string $key
     *
     * @return void
     */
    public function _delete($key)
    {
        $key = $this->_getKey($key);

        $this->redis->hDel($key[0], $key[1]);
    }
}