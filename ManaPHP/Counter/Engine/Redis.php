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
    protected $_prefix;

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
        $this->_dependencyInjector->setAliases('redis', 'counterRedis');

        if ($this->_prefix === null) {
            $this->_prefix = $this->_dependencyInjector->configure->appID . ':counter:';
        }

        return $this;
    }

    /**
     * @param string $prefix
     *
     * @return static
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;

        return $this;
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return int
     */
    public function get($type, $id)
    {
        return (int)$this->counterRedis->hGet($this->_prefix . $type, $id);
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
        return $this->counterRedis->hIncrBy($this->_prefix . $type, $id, $step);
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return void
     */
    public function delete($type, $id)
    {
        $this->counterRedis->hDel($this->_prefix . $type, $id);
    }
}