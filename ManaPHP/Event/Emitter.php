<?php

namespace ManaPHP\Event;

class Emitter implements EmitterInterface
{
    /**
     * @var callable[]
     */
    protected $on;

    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return void
     */
    public function on($event, $handler)
    {
        $this->on[$event] = $handler;
    }

    /**
     * @param string $event
     * @param mixed  $data
     *
     * @return mixed
     */
    public function emit($event, $data = null)
    {
        if (($handler = $this->on[$event] ?? null) !== null) {
            return $handler($data);
        } else {
            return null;
        }
    }
}