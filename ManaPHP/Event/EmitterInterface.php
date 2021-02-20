<?php

namespace ManaPHP\Event;

interface EmitterInterface
{
    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return void
     */
    public function on($event, $handler);

    /**
     * @param string $event
     * @param mixed  $data
     *
     * @return mixed
     */
    public function emit($event, $data = null);
}