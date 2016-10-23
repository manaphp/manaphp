<?php
namespace ManaPHP\Counter\Adapter;

use ManaPHP\Component;
use ManaPHP\Counter\AdapterInterface;

/**
 * Class ManaPHP\Counter\Adapter\Redis
 *
 * @package ManaPHP\Counter\Adapter
 *
 * @property \Redis $redis
 */
class Redis extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_prefix = 'manaphp:counter:';

    /**
     * Redis constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        } elseif (is_string($options)) {
            $options = ['prefix' => $options];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return int
     */
    public function get($type, $id)
    {
        return (int)$this->redis->hGet($this->_prefix . $type, $id);
    }

    /**
     * @param string $type
     * @param string $id
     * @param int    $step
     *
     * @return int
     */
    public function increment($type, $id, $step = 1)
    {
        return $this->redis->hIncrBy($this->_prefix . $type, $id, $step);
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return void
     */
    public function delete($type, $id)
    {
        $this->redis->hDel($this->_prefix . $type, $id);
    }
}