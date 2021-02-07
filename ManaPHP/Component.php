<?php

namespace ManaPHP;

use JsonSerializable;
use ManaPHP\Coroutine\Context\Inseparable;
use ManaPHP\Di\Injectable;
use ManaPHP\Event\EventArgs;
use Swoole\Coroutine;

/**
 * @property-read \ManaPHP\AliasInterface                  $alias
 * @property-read \ManaPHP\Event\ManagerInterface          $eventsManager
 * @property-read \ManaPHP\Logging\LoggerInterface         $logger
 * @property-read \ManaPHP\Configuration\Configure         $configure
 * @property-read \ManaPHP\Configuration\SettingsInterface $settings
 * @property-read \ManaPHP\Security\CryptInterface         $crypt
 * @property-read \ManaPHP\Identifying\IdentityInterface   $identity
 * @property-read \ManaPHP\Caching\CacheInterface          $cache
 * @property-read \ManaPHP\Http\ClientInterface            $httpClient
 * @property-read \ManaPHP\Http\ClientInterface            $restClient
 * @property-read \ManaPHP\Data\DbInterface                $db
 * @property-read \Redis|\ManaPHP\Data\RedisInterface      $redisCache
 * @property-read \Redis|\ManaPHP\Data\RedisInterface      $redisDb
 * @property-read \Redis|\ManaPHP\Data\RedisInterface      $redisBroker
 * @property-read \ManaPHP\Data\MongodbInterface           $mongodb
 * @property-read \ManaPHP\Messaging\AmqpInterface         $rabbitmq
 * @property-read \Elasticsearch\Client                    $elasticsearch
 * @property-read \ManaPHP\Mailing\MailerInterface         $mailer
 * @property-read \ManaPHP\Bos\ClientInterface             $bosClient
 * @property-read \ManaPHP\Ws\Pushing\ClientInterface      $wspClient
 * @property-read \ManaPHP\Coroutine\ManagerInterface      $coroutineManager
 * @property-read \ManaPHP\Ws\ClientInterface              $wsClient
 * @property-read \ManaPHP\Messaging\PubSubInterface       $pubSub
 * @property-read \object                                  $context
 */
class Component implements Injectable, JsonSerializable
{
    /**
     * @var int
     */
    protected $object_id;

    /**
     * @var \ManaPHP\Di\ContainerInterface
     */
    protected $container;

    /**
     * @var callable[]
     */
    protected $on;

    /**
     * @var array
     */
    protected $injections;

    /**
     * @param string $class
     * @param array  $params
     *
     * @return mixed
     */
    public function getNew($class, $params = [])
    {
        return $this->container->getNew($class, $params);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getShared($name)
    {
        return $this->container->getShared($this->injections[$name] ?? $name);
    }

    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     *
     * @return static
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @return \ManaPHP\Di\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param string $name
     * @param mixed  $target
     *
     * @return static
     */
    public function inject($name, $target)
    {
        $this->injections[$name] = $target;

        return $this;
    }

    /**
     * @return object
     */
    protected function createContext()
    {
        static $cached = [];

        $class = static::class;
        if (!$context = $cached[$class] ?? null) {
            $parent = $class;
            do {
                $try = $parent . 'Context';
                if (class_exists($try, false)) {
                    $context = $try;
                    break;
                }
            } while ($parent = get_parent_class($parent));

            if ($context === null) {
                throw new Exception(['`%s` context class is not exists', get_class($this) . 'Context']);
            }

            $cached[$class] = $context;
        }

        return new $context();
    }

    /**
     * @return object
     */
    protected function _getContext()
    {
        global $__root_context;

        if (!$object_id = $this->object_id) {
            $object_id = $this->object_id = spl_object_id($this);
        }

        if (MANAPHP_COROUTINE_ENABLED) {
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
        static $cached = [];

        $class = static::class;
        if (($context = $cached[$class] ?? null) === null) {
            $parent = $class;
            do {
                $try = $parent . 'Context';
                if (class_exists($try, false)) {
                    $context = $try;
                    break;
                }
            } while ($parent = get_parent_class($parent));

            $cached[$class] = $context !== null;
        }

        return $cached[$class];
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === 'context') {
            return $this->_getContext();
        } else {
            return $this->{$name} = $this->getShared($name);
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->container->has($name);
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
        $this->eventsManager->attachEvent($event, $handler, $priority);

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
        $this->eventsManager->detachEvent($event, $handler);

        return $this;
    }

    /**
     * @param string   $group
     * @param callable $handler
     *
     * @return static
     */
    protected function peekEvent($group, $handler)
    {
        $this->eventsManager->peekEvent($group, $handler);

        return $this;
    }

    /**
     * Fires an event in the events manager causing that the active listeners will be notified about it
     *
     * @param string $event
     * @param mixed  $data
     * @param mixed  $source
     *
     * @return \ManaPHP\Event\EventArgs
     */
    protected function fireEvent($event, $data = null, $source = null)
    {
        $on = substr($event, strpos($event, ':') + 1);

        if (isset($this->on[$on])) {
            $this->emit($on, $data);
        }

        return $this->eventsManager->fireEvent($event, $data, $source ?? $this);
    }

    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return static
     */
    public function on($event, $handler)
    {
        $this->on[$event][] = $handler;

        return $this;
    }

    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return static
     */
    public function off($event = null, $handler = null)
    {
        if ($event === null) {
            $this->on = null;
        } elseif ($handler === null) {
            unset($this->on[$event]);
        } else {
            foreach ($this->on[$event] as $i => $v) {
                if ($v === $handler) {
                    unset($this->on[$event[$i]]);
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * @param string $event
     * @param array  $data
     *
     * @return void
     */
    public function emit($event, $data = [])
    {
        $eventArgs = new EventArgs($event, $this, $data);

        foreach ($this->on[$event] ?? [] as $handler) {
            $handler($eventArgs);
        }
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $data = [];
        foreach (get_object_vars($this) as $k => $v) {
            if ($k === 'object_id' || $k === 'container' || $k === 'on') {
                continue;
            }

            if (PHP_SAPI !== 'apache2handler' && is_object($k)) {
                continue;
            }

            $data[$k] = $v;
        }

        if (isset($data['context'])) {
            $data['context'] = (array)$data['context'];
        } elseif ($this->hasContext()) {
            $data['context'] = (array)$this->_getContext();
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
            if ($k === 'object_id' || $k === 'on' || (is_object($v) && $k !== 'context')) {
                continue;
            }

            $data[$k] = $v instanceof self ? $v->dump() : $v;
        }

        if (isset($data['context'])) {
            $data['context'] = (array)$data['context'];
        } elseif ($this->object_id !== null) {
            $data['context'] = (array)$this->__get('context');
        }

        if ($data['injections'] === null) {
            unset($data['injections']);
        }

        return $data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = [];

        foreach (get_object_vars($this) as $k => $v) {
            if ($v === null || is_scalar($v)) {
                $data[$k] = $v;
            }
        }

        return $data;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
