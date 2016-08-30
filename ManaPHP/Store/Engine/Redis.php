<?php

namespace ManaPHP\Store\Engine;

use ManaPHP\Component;
use ManaPHP\Store\EngineInterface;

/**
 * Class Redis
 *
 * @package ManaPHP\Store\Adapter
 *
 * @property \Redis $redis
 */
class Redis extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_key = 'manaphp:store:';

    /**
     * Redis constructor.
     *
     * @param string|array|\ConfManaPHP\Store\Engine\Redis $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        } elseif (is_string($options)) {
            $options = ['key' => $options];
        }

        if (isset($options['key'])) {
            $this->_key .= $options['key'];
        }
    }

    /**
     * Fetch content
     *
     * @param string $id
     *
     * @return string|false
     * @throws \ManaPHP\Store\Engine\Exception
     */
    public function get($id)
    {
        return $this->redis->hGet($this->_key, $id);
    }

    /**
     * Caches content
     *
     * @param string $id
     * @param string $value
     *
     * @return void
     * @throws \ManaPHP\Store\Engine\Exception
     */
    public function set($id, $value)
    {
        $this->redis->hSet($this->_key, $id, $value);
    }

    /**
     * Delete content
     *
     * @param string $id
     *
     * @void
     * @throws \ManaPHP\Store\Engine\Exception
     */
    public function delete($id)
    {
        $this->redis->hDel($this->_key, $id);
    }

    /**
     * Check if id exists
     *
     * @param string $id
     *
     * @return bool
     * @throws \ManaPHP\Store\Engine\Exception
     */
    public function exists($id)
    {
        return $this->redis->hExists($this->_key, $id);
    }
}