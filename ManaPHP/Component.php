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
 * @property-read \object                                  $_context
 * @property-read \ManaPHP\DiInterface                     $_di
 */
class Component implements ComponentInterface, Injectable, JsonSerializable
{
    /**
     * @var int
     */
    protected $_object_id;

    /**
     * @var callable[]
     */
    protected $_on;

    /**
     * @var array
     */
    protected $_injections;

    /**
     * @param string $class
     * @param array  $params
     *
     * @return mixed
     */
    public function getNew($class, $params = [])
    {
        return $this->_di->get($class, $params);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getShared($name)
    {
        return $this->_di->getShared($this->_injections[$name] ?? $name);
    }

    /**
     * @param string $name
     * @param mixed  $target
     *
     * @return static
     */
    public function inject($name, $target)
    {
        if ($name === 'di') {
            $this->_di = $target;
        } else {
            $this->_injections[$name] = $target;
        }

        return $this;
    }

    /**
     * @return object
     */
    protected function _createContext()
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
     * Magic method __get
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === '_context') {
            global $__root_context;

            if (!$object_id = $this->_object_id) {
                $object_id = $this->_object_id = spl_object_id($this);
            }

            if (MANAPHP_COROUTINE_ENABLED) {
                if ($context = Coroutine::getContext()) {
                    if (!$object = $context[$object_id] ?? null) {
                        if (($parent_cid = Coroutine::getPcid()) === -1) {
                            return $context[$object_id] = $this->_createContext();
                        }

                        $parent_context = Coroutine::getContext($parent_cid);
                        if ($object = $parent_context[$object_id] ?? null) {
                            if ($object instanceof Inseparable) {
                                return $context[$object_id] = $this->_createContext();
                            } else {
                                return $context[$object_id] = $object;
                            }
                        } else {
                            $object = $context[$object_id] = $this->_createContext();
                            if (!$object instanceof Inseparable) {
                                $parent_context[$object_id] = $object;
                            }
                        }
                    }
                    return $object;
                } elseif (!$object = $__root_context[$object_id] ?? null) {
                    return $__root_context[$object_id] = $this->_createContext();
                } else {
                    return $object;
                }
            } else {
                $__root_context[] = $this;

                return $this->_context = $this->_createContext();
            }
        } elseif ($name === '_di') {
            return $this->_di = Di::getDefault();
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
        return $this->_di->has($name);
    }

    /**
     * Attach a listener to the events manager
     *
     * @param string   $event
     * @param callable $handler
     * @param bool     $appended
     *
     * @return static
     */
    public function attachEvent($event, $handler, $appended = true)
    {
        $this->eventsManager->attachEvent($event, $handler, $appended);

        return $this;
    }

    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return static
     */
    public function detachEvent($event, $handler)
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
    public function peekEvent($group, $handler)
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
    public function fireEvent($event, $data = null, $source = null)
    {
        $on = substr($event, strpos($event, ':') + 1);

        if (isset($this->_on[$on])) {
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
        $this->_on[$event][] = $handler;

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
            $this->_on = null;
        } elseif ($handler === null) {
            unset($this->_on[$event]);
        } else {
            foreach ($this->_on[$event] as $i => $v) {
                if ($v === $handler) {
                    unset($this->_on[$event[$i]]);
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

        foreach ($this->_on[$event] ?? [] as $handler) {
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
            if ($k === '_object_id' || $k === '_di' || $k === '_on') {
                continue;
            }

            if (PHP_SAPI !== 'apache2handler' && is_object($k)) {
                continue;
            }

            $data[$k] = $v;
        }

        if (isset($data['_context'])) {
            $data['_context'] = (array)$data['_context'];
        } elseif ($this->_object_id !== null) {
            $data['_context'] = (array)$this->__get('_context');
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
            if ($k === '_object_id' || $k === '_on' || (is_object($v) && $k !== '_context')) {
                continue;
            }

            $data[$k] = $v instanceof self ? $v->dump() : $v;
        }

        if (isset($data['_context'])) {
            $data['_context'] = (array)$data['_context'];
        } elseif ($this->_object_id !== null) {
            $data['_context'] = (array)$this->__get('_context');
        }

        if ($data['_injections'] === null) {
            unset($data['_injections']);
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
