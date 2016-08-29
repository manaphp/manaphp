<?php

namespace ManaPHP;

interface CounterInterface
{
    /**
     *
     * @param string $type
     * @param string $id
     *
     * @return int
     */
    public function get($type, $id);

    /**
     * Increments the value of key by a given step.
     *
     * @param string $type
     * @param string $id
     * @param int    $step
     *
     * @return int the new value
     */
    public function increment($type, $id, $step = 1);

    /**
     * Decrements the value of key by a given step.
     *
     * @param string $type
     * @param string $id
     * @param int    $step
     *
     * @return int the new value
     */
    public function decrement($type, $id, $step = 1);

    /**
     * Deletes the key
     *
     * @param string $type
     * @param string $id
     *
     * @return void
     */
    public function delete($type, $id);
}