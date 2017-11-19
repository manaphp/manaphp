<?php
namespace ManaPHP;

use ManaPHP\Counter\EngineInterface;

/**
 * Class ManaPHP\Counter
 *
 * @package counter
 */
class Counter extends Component implements CounterInterface
{
    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * @var \ManaPHP\Counter\EngineInterface
     */
    protected $_engine;

    /**
     * Counter constructor.
     *
     * @param string|array|EngineInterface $options
     */
    public function __construct($options = 'ManaPHP\Counter\Engine\Redis')
    {
        if (is_string($options) || is_object($options)) {
            $options = ['engine' => $options];
        }

        if (isset($options['engine'])) {
            $this->_engine = $options['engine'];
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

        if (!is_object($this->_engine)) {
            $this->_engine = $this->_dependencyInjector->getShared($this->_engine);
        }

        return $this;
    }

    /**
     *
     * @param string $key
     *
     * @return int
     */
    public function get($key)
    {
        return $this->_engine->get($this->_prefix . $key);
    }

    /**
     * Increments the value of key by a given step.
     *
     * @param string $key
     * @param int    $step
     *
     * @return int the new value
     */
    public function increment($key, $step = 1)
    {
        return $this->_engine->increment($this->_prefix . $key, $step);
    }

    /**
     * Decrements the value of key by a given step.
     *
     * @param string $key
     * @param int    $step
     *
     * @return int the new value
     */
    public function decrement($key, $step = 1)
    {
        return $this->_engine->increment($this->_prefix . $key, -$step);
    }

    /**
     * Deletes the key
     *
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->_engine->delete($this->_prefix . $key);
    }
}