<?php
declare(strict_types=1);

namespace ManaPHP;

use JsonSerializable;
use ManaPHP\Helper\Container;
use Psr\Container\ContainerInterface;

/**
 * @property-read \ManaPHP\ContextorInterface $contextor
 */
class Component implements JsonSerializable
{
    protected array $__dynamicProperties = [];

    public function __get(string $name): mixed
    {
        if ($name === 'context') {
            return $this->contextor->getContext($this);
        } else {
            if (($value = $this->__dynamicProperties[$name] ?? null) === null) {
                $value = $this->__dynamicProperties[$name] = Container::inject($this, $name);
            }

            return $value;
        }
    }

    public function __set(string $name, $value): void
    {
        $this->__dynamicProperties[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->__dynamicProperties[$name]);
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
            if ($k === '__dynamicProperties') {
                continue;
            }

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
