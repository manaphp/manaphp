<?php /** @noinspection MagicMethodsValidityInspection */

namespace ManaPHP;

use JsonSerializable;
use ManaPHP\Coroutine\Context\Inseparable;
use ManaPHP\Di\Injectable;
use Swoole\Coroutine;

/**
 * @property-read \ManaPHP\Event\ManagerInterface $eventManager
 * @property-read \object                         $context
 */
class Component implements Injectable, JsonSerializable
{
    /**
     * @var \ManaPHP\Di\ContainerInterface
     */
    protected $container;

    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     *
     * @return void
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return string|null
     */
    protected function findContext()
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

    /**
     * @return object
     */
    protected function createContext()
    {
        if (($context = $this->findContext()) === null) {
            throw new Exception(['`%s` context class is not exists', get_class($this) . 'Context']);
        }

        return new $context();
    }

    /**
     * @return object
     */
    protected function getContext()
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
        } else {
            $__root_context[] = $this;

            return $this->context = $this->createContext();
        }
    }

    /**
     * @return bool
     */
    protected function hasContext()
    {
        return $this->findContext() !== null;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === 'context') {
            return $this->getContext();
        } else {
            return $this->container->inject($this, $name);
        }
    }

    /**
     * Attach a listener to the events manager
     *
     * @param string   $event
     * @param callable $handler
     * @param int      $priority
     *
     * @return static
     */
    protected function attachEvent($event, $handler, $priority = 0)
    {
        $this->eventManager->attachEvent($event, $handler, $priority);

        return $this;
    }

    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return static
     */
    protected function detachEvent($event, $handler)
    {
        $this->eventManager->detachEvent($event, $handler);

        return $this;
    }

    /**
     * Fires an event in the events manager causing that the active listeners will be notified about it
     *
     * @param string $event
     * @param mixed  $data
     *
     * @return \ManaPHP\Event\EventArgs
     */
    protected function fireEvent($event, $data = null)
    {
        return $this->eventManager->fireEvent($event, $data, $this);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $data = [];

        foreach (get_object_vars($this) as $k => $v) {
            if ($k === 'container' || $v === null || $v instanceof Injectable) {
                continue;
            }

            $data[$k] = $v;
        }

        if (!isset($data['context']) && $this->hasContext()) {
            $data['context'] = $this->getContext();
        }

        return $data;
    }

    /**
     * @return array
     */
    public function dump()
    {
        $data = [];

        foreach (get_object_vars($this) as $k => $v) {
            if (is_object($v)) {
                continue;
            }

            $data[$k] = $v;
        }

        if (!isset($data['context']) && $this->hasContext()) {
            $data['context'] = (array)$this->getContext();
        }

        return $data;
    }

    public function jsonSerialize()
    {
        return $this->__debugInfo();
    }
}
