<?php
namespace ManaPHP;

use ManaPHP\Counter\AdapterInterface;

abstract class Counter extends Component implements CounterInterface, AdapterInterface
{
    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * Counter constructor.
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
     *
     * @param string $type
     * @param string $id
     *
     * @return int
     */
    public function get($type, $id)
    {
        return $this->_get($this->_prefix . $type, $id);
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
        return $this->_increment($this->_prefix . $type, $id, $step);
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
        return $this->_increment($this->_prefix . $type, $id, -$step);
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
        $this->_delete($this->_prefix . $type, $id);
    }
}