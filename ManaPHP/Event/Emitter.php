<?php
declare(strict_types=1);

namespace ManaPHP\Event;

class Emitter implements EmitterInterface
{
    /**
     * @var callable[]
     */
    protected array $on = [];

    public function on(string $event, callable $handler): void
    {
        $this->on[$event] = $handler;
    }

    /**
     * @param string $event
     * @param mixed  $data
     *
     * @return mixed
     */
    public function emit(string $event, mixed $data = null): mixed
    {
        if (($handler = $this->on[$event] ?? null) !== null) {
            return $handler($data);
        } else {
            return null;
        }
    }
}