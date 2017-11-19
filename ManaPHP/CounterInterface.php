<?php

namespace ManaPHP;

/**
 * Interface ManaPHP\CounterInterface
 *
 * @package counter
 */
interface CounterInterface
{
    /**
     *
     * @param string $key
     *
     * @return int
     */
    public function get($key);

    /**
     * Increments the value of key by a given step.
     *
     * @param string $key
     * @param int    $step
     *
     * @return int the new value
     */
    public function increment($key, $step = 1);

    /**
     * Decrements the value of key by a given step.
     *
     * @param string $key
     * @param int    $step
     *
     * @return int the new value
     */
    public function decrement($key, $step = 1);

    /**
     * Deletes the key
     *
     * @param string $key
     *
     * @return void
     */
    public function delete($key);
}