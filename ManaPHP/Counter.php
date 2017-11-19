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
    public function __construct($options = [])
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
     * @param string $type
     * @param string $id
     *
     * @return int
     */
    public function get($type, $id)
    {
        return $this->_engine->get($this->_prefix . $type, $id);
    }

    /**
     * Increments the value of key by a given step.
     *
     * @param string $type
     * @param string $id
     * @param int    $step
     *
     * @return int the new value
     */
    public function increment($type, $id, $step = 1)
    {
        return $this->_engine->increment($this->_prefix . $type, $id, $step);
    }

    /**
     * Decrements the value of key by a given step.
     *
     * @param string $type
     * @param string $id
     * @param int    $step
     *
     * @return int the new value
     */
    public function decrement($type, $id, $step = 1)
    {
        return $this->_engine->increment($this->_prefix . $type, $id, -$step);
    }

    /**
     * Deletes the key
     *
     * @param string $type
     * @param string $id
     *
     * @return void
     */
    public function delete($type, $id)
    {
        $this->_engine->delete($this->_prefix . $type, $id);
    }
}