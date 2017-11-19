<?php
namespace ManaPHP\Counter\Engine;

use ManaPHP\Component;
use ManaPHP\Counter\EngineInterface;

/**
 * Class ManaPHP\Counter\Engine\Redis
 *
 * @package counter\adapter
 *
 * @property \Redis $counterRedis
 */
class Redis extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_prefix = 'counter:';

    /**
     * Redis constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
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
        $this->_dependencyInjector->setAliases('redis', 'counterRedis');

        return $this;
    }

    /**
     * @param string $key
     *
     * @return int
     */
    public function get($key)
    {
        return (int)$this->counterRedis->get($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param int    $step
     *
     * @return int
     */
    public function increment($key, $step = 1)
    {
        return $this->counterRedis->incrBy($this->_prefix . $key, $step);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->counterRedis->delete($this->_prefix . $key);
    }
}