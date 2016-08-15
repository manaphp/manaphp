<?php

namespace ManaPHP;

interface CounterInterface
{
    /**
     * Get the value of key
     *
     * @param string|array $key
     *
     * @return int
     */
    public function get($key);

    /**
     * Increments the value of key by a given step.
     *
     * @param string|array $key
     * @param int          $step
     *
     * @return int the new value
     */
    public function increment($key, $step = 1);

    /**
     * Decrements the value of key by a given step.
     *
     * @param  string|array $key
     * @param int           $step
     *
     * @return int the new value
     */
    public function decrement($key, $step = 1);

    /**
     * Deletes the key
     *
     * @param string|array $key
     *
     * @return void
     */
    public function delete($key);
}