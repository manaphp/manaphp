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
     * @return \ManaPHP\Counter\EngineInterface
     */
    protected function _getEngine()
    {
        if (is_string($this->_engine)) {
            return $this->_engine = $this->_dependencyInjector->getShared($this->_engine);
        } else {
            return $this->_engine = $this->_dependencyInjector->getInstance($this->_engine);
        }
    }

    /**
     *
     * @param string $key
     *
     * @return int
     */
    public function get($key)
    {
        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        return $engine->get($this->_prefix . $key);
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
        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        return $engine->increment($this->_prefix . $key, $step);
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
        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        return $engine->increment($this->_prefix . $key, -$step);
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
        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        $engine->delete($this->_prefix . $key);
    }
}