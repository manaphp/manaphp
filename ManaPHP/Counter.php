<?php
namespace ManaPHP;

use ManaPHP\Counter\AdapterInterface;

class Counter extends Component implements CounterInterface
{
    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * @var \ManaPHP\Counter\AdapterInterface
     */
    public $adapter;

    /**
     * Counter constructor.
     *
     * @param string|array|AdapterInterface $options
     */
    public function __construct($options = [])
    {
        if ($options instanceof AdapterInterface || is_string($options)) {
            $options = ['adapter' => $options];
        } elseif (is_object($options)) {
            $options = (array)$options;
        }

        if (isset($options['adapter'])) {
            $this->adapter = $options['adapter'];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        if (!is_object($this->adapter)) {
            $this->adapter = $this->_dependencyInjector->getShared($this->adapter);
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
        return $this->adapter->get($this->_prefix . $type, $id);
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
        return $this->adapter->increment($this->_prefix . $type, $id, $step);
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
        return $this->adapter->increment($this->_prefix . $type, $id, -$step);
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
        $this->adapter->delete($this->_prefix . $type, $id);
    }
}