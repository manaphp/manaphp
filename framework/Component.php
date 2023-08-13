<?php
declare(strict_types=1);

namespace ManaPHP;

use JsonSerializable;
use ManaPHP\Context\ContextorInterface;
use ManaPHP\Helper\Container;
use Psr\Container\ContainerInterface;

class Component implements JsonSerializable
{
    public function __debugInfo(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $k => $v) {
            if ($v === null || $v instanceof ContainerInterface || $v instanceof self) {
                continue;
            }

            $data[$k] = $v;
        }

        $contextor = Container::get(ContextorInterface::class);
        if ($contextor->hasContext($this)) {
            $data['context'] = $contextor->getContext($this);
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

        $contextor = Container::get(ContextorInterface::class);
        if ($contextor->hasContext($this)) {
            $data['context'] = (array)$contextor->getContext($this);
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->__debugInfo();
    }
}
