<?php
declare(strict_types=1);

namespace ManaPHP;

use JsonSerializable;
use ManaPHP\Event\EventArgs;
use ManaPHP\Helper\Container;
use Psr\Container\ContainerInterface;

/**
 * @property-read \ManaPHP\ContextorInterface     $contextor
 * @property-read \ManaPHP\Event\ManagerInterface $eventManager
 */
class Component implements JsonSerializable
{
    /** @noinspection MagicMethodsValidityInspection */
    public function __get(string $name): mixed
    {
        if ($name === 'context') {
            return $this->contextor->getContext($this);
        } else {
            return Container::inject($this, $name);
        }
    }

    protected function attachEvent(string $event, callable $handler, int $priority = 0): static
    {
        $this->eventManager->attachEvent($event, $handler, $priority);

        return $this;
    }

    protected function detachEvent(string $event, callable $handler): static
    {
        $this->eventManager->detachEvent($event, $handler);

        return $this;
    }

    protected function fireEvent(string $event, mixed $data = null): EventArgs
    {
        return $this->eventManager->fireEvent($event, $data, $this);
    }

    public function __debugInfo(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $k => $v) {
            if ($v === null || $v instanceof ContainerInterface || $v instanceof self) {
                continue;
            }

            $data[$k] = $v;
        }

        if ($this->contextor->hasContext($this)) {
            $data['context'] = $this->contextor->getContext($this);
        }

        return $data;
    }

    public function dump(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $k => $v) {
            if (is_object($v)) {
                continue;
            }

            $data[$k] = $v;
        }

        if ($this->contextor->hasContext($this)) {
            $data['context'] = (array)$this->contextor->getContext($this);
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->__debugInfo();
    }
}
