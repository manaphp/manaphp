<?php

namespace ManaPHP;

use JsonSerializable;
use ManaPHP\Coroutine\Context\Inseparable;
use ManaPHP\Di\Injectable;
use ManaPHP\Event\EventArgs;
use Swoole\Coroutine;

/**
 * Class ManaPHP\Component
 *
 * @package component
 *
 * @property-read \ManaPHP\AliasInterface                  $alias
 * @property-read \ManaPHP\Event\ManagerInterface          $eventsManager
 * @property-read \ManaPHP\Logging\LoggerInterface         $logger
 * @property-read \ManaPHP\Configuration\Configure         $configure
 * @property-read \ManaPHP\Configuration\SettingsInterface $settings
 * @property-read \ManaPHP\Security\CryptInterface         $crypt
 * @property-read \ManaPHP\IdentityInterface               $identity
 * @property-read \ManaPHP\Loader                          $loader
 * @property-read \ManaPHP\Caching\CacheInterface          $cache
 * @property-read \ManaPHP\Security\RandomInterface        $random
 * @property-read \ManaPHP\Http\ClientInterface            $httpClient
 * @property-read \ManaPHP\Http\ClientInterface            $restClient
 * @property-read \ManaPHP\DbInterface                     $db
 * @property-read \Redis|\ManaPHP\RedisInterface           $redisCache
 * @property-read \Redis|\ManaPHP\RedisInterface           $redisDb
 * @property-read \Redis|\ManaPHP\RedisInterface           $redisBroker
 * @property-read \ManaPHP\MongodbInterface                $mongodb
 * @property-read \ManaPHP\Messaging\AmqpInterface         $rabbitmq
 * @property-read \Elasticsearch\Client                    $elasticsearch
 * @property-read \ManaPHP\Mailing\MailerInterface         $mailer
 * @property-read \ManaPHP\Ipc\CacheInterface              $ipcCache
 * @property-read \ManaPHP\Bos\ClientInterface             $bosClient
 * @property-read \ManaPHP\Pool\ManagerInterface           $poolManager
 * @property-read \ManaPHP\ValidatorInterface              $validator
 * @property-read \ManaPHP\I18n\TranslatorInterface        $translator
 * @property-read \ManaPHP\Ws\PusherInterface              $wsPusher
 * @property-read \ManaPHP\Coroutine\ManagerInterface      $coroutineManager
 * @property-read \ManaPHP\Ws\ClientInterface              $wsClient
 * @property-read \ManaPHP\Messaging\PubSubInterface       $pubSub
 * @property-read \object                                  $_context
 */
class Component implements ComponentInterface, Injectable, JsonSerializable
{
    /**
     * @var int
     */
    protected $_object_id;

    /**
     * @var \ManaPHP\Di
     */
    protected $_di;

    /**
     * @var callable[]
     */
    protected $_on;

    /**
     * Sets the dependency injector
     *
     * @param \ManaPHP\DiInterface $di
     *
     * @return void
     */
    public function setDi($di)
    {
        $this->_di = $di;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \ManaPHP\Di
     */
    public function getDi()
    {
        return $this->_di;
    }

    /**
     * @param string $class
     * @param array  $params
     *
     * @return mixed
     */
    public function getInstance($class, $params = [])
    {
        if ($this->_di === null) {
            $this->_di = Di::getDefault();
        }

        return $this->_di->get($class, $params);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getShared($name)
    {
        if ($this->_di === null) {
            $this->_di = Di::getDefault();
        }

        return $this->_di->getShared($name);
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
        if ($this->_di === null) {
            $this->_di = Di::getDefault();
        }

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
     * @return void
     */
    public function fireEvent($event, $data = [], $source = null)
    {
        $on = substr($event, strpos($event, ':') + 1);

        if (isset($this->_on[$on])) {
            $this->emit($on, $data);
        }

        $this->eventsManager->fireEvent($event, $data, $source ?? $this);
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
