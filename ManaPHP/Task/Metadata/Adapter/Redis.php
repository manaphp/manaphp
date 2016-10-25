<?php
namespace ManaPHP\Task\Metadata\Adapter;

use ManaPHP\Component;
use ManaPHP\Task\Metadata\AdapterInterface;

/**
 * Class ManaPHP\Task\Metadata\Adapter\Redis
 *
 * @package tasksMetadata\adapter
 *
 * @property \Redis               $redis
 * @property \ManaPHP\DiInterface $redisDi
 */
class Redis extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_prefix = 'manaphp:task:';

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
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        if (isset($this->redisDi)) {
            $this->redis = $this->redisDi->getShared('task', ['prefix' => $this->_prefix]);
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key)
    {
        return $this->redis->get($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set($key, $value)
    {
        $this->redis->set($this->_prefix . $key, $value);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->redis->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->redis->exists($this->_prefix . $key);
    }
}