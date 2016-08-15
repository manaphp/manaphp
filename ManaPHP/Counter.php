<?php
namespace ManaPHP;

use ManaPHP\Counter\AdapterInterface;

abstract class Counter extends Component implements CounterInterface, AdapterInterface
{
    /**
     * @param array|string $key
     *
     * @return int
     */
    public function get($key)
    {
        return $this->_get($key);
    }

    /**
     * @param array|string $key
     * @param int          $step
     *
     * @return int
     */
    public function increment($key, $step = 1)
    {
        return $this->_increment($key, $step);
    }

    /**
     * @param array|string $key
     * @param int          $step
     *
     * @return int
     */
    public function decrement($key, $step = 1)
    {
        return $this->_increment($key, -$step);
    }

    /**
     * @param array|string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->_delete($key);
    }
}