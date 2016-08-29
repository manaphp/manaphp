<?php
namespace ManaPHP\Counter\Adapter;

use ManaPHP\Counter;

/**
 * Class Redis
 *
 * @package ManaPHP\Counter\Adapter
 *
 * @property \Redis $redis
 */
class Redis extends Counter
{
    /**
     * Redis constructor.
     *
     * @param array|string $options
     */
    public function __construct($options)
    {
        $this->_prefix = 'manaphp:counter:';

        parent::__construct($options);
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return int
     */
    public function _get($type, $id)
    {
        return (int)$this->redis->hGet($type, $id);
    }

    /**
     * @param string $type
     * @param string $id
     * @param int    $step
     *
     * @return int
     */
    public function _increment($type, $id, $step = 1)
    {
        return $this->redis->hIncrBy($type, $id, $step);
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return void
     */
    public function _delete($type, $id)
    {
        $this->redis->hDel($type, $id);
    }
}