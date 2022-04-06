<?php
declare(strict_types=1);

namespace ManaPHP;

use JsonSerializable;
use ManaPHP\Coroutine\Context\Inseparable;
use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\Injectable;
use ManaPHP\Event\EventArgs;
use ManaPHP\Helper\Container;
use Swoole\Coroutine;

/**
 * @property-read \ManaPHP\Event\ManagerInterface $eventManager
 * @property-read \object                         $context
 */
class Component implements Injectable, JsonSerializable
{
    protected ContainerInterface $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    protected function findContext(): ?string
    {
        static $cached = [];

        $class = static::class;
        if (($context = $cached[$class] ?? null) === null) {
            $parent = $class;
            do {
                $try = $parent . 'Context';
                if (class_exists($try)) {
                    $context = $try;
                    break;
                }
            } while ($parent = get_parent_class($parent));

            if ($context === null) {
                return null;
            }

            $cached[$class] = $context;
        }

        return $context;
    }

    protected function createContext(): object
    {
        if (($context = $this->findContext()) === null) {
            throw new Exception(['`%s` context class is not exists', get_class($this) . 'Context']);
        }

        return new $context();
    }

    protected function getContext(): object
    {
        global $__root_context;

        if (MANAPHP_COROUTINE_ENABLED) {
            $object_id = spl_object_id($this);

            if ($context = Coroutine::getContext()) {
                if (!$object = $context[$object_id] ?? null) {
                    if (($parent_cid = Coroutine::getPcid()) === -1) {
                        return $context[$object_id] = $this->createContext();
                    }

                    $parent_context = Coroutine::getContext($parent_cid);
                    if ($object = $parent_context[$object_id] ?? null) {
                        if ($object instanceof Inseparable) {
                            return $context[$object_id] = $this->createContext();
                        } else {
                            return $context[$object_id] = $object;
                        }
                    } else {
                        $object = $context[$object_id] = $this->createContext();
                        if (!$object instanceof Inseparable) {
                            $parent_context[$object_id] = $object;
                        }
                    }
                }
                return $object;
            } elseif (!$object = $__root_context[$object_id] ?? null) {
                return $__root_context[$object_id] = $this->createContext();
            } else {
                return $object;
            }
        } elseif (isset($this->context)) {
            return $this->context;
        } else {
            $__root_context[] = $this;

            return $this->context = $this->createContext();
        }
    }

    protected function hasContext(): bool
    {
        return $this->findContext() !== null;
    }

    /** @noinspection MagicMethodsValidityInspection */
    public function __get(string $name): mixed
    {
        if ($name === 'context') {
            return $this->getContext();
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
            if ($k === 'container' || $v === null || $v instanceof Injectable) {
                continue;
            }

            $data[$k] = $v;
        }

        if ($this->hasContext()) {
            $data['context'] = $this->getContext();
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

        if ($this->hasContext()) {
            $data['context'] = (array)$this->getContext();
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->__debugInfo();
    }
}
