<?php
namespace ManaPHP;

use JsonSerializable;
use ManaPHP\Coroutine\Context\Inseparable;
use Swoole\Coroutine;

/**
 * Class ManaPHP\Component
 *
 * @package component
 *
 * @property-read \ManaPHP\AliasInterface                  $alias
 * @property-read \ManaPHP\Event\ManagerInterface          $eventsManager
 * @property-read \ManaPHP\FilesystemInterface             $filesystem
 * @property-read \ManaPHP\LoggerInterface                 $logger
 * @property-read \ManaPHP\Configuration\Configure         $configure
 * @property-read \ManaPHP\Configuration\SettingsInterface $settings
 * @property-read \ManaPHP\Security\CryptInterface         $crypt
 * @property-read \ManaPHP\IdentityInterface               $identity
 * @property-read \ManaPHP\Loader                          $loader
 * @property-read \ManaPHP\CacheInterface                  $cache
 * @property-read \ManaPHP\CacheInterface                  $redisCache
 * @property-read \ManaPHP\Security\RandomInterface        $random
 * @property-read \ManaPHP\Http\ClientInterface            $httpClient
 * @property-read \ManaPHP\Http\ClientInterface            $restClient
 * @property-read \ManaPHP\DbInterface                     $db
 * @property-read \Redis                                   $redis
 * @property-read \ManaPHP\Mongodb                         $mongodb
 * @property-read \ManaPHP\AmqpInterface                   $rabbitmq
 * @property-read \Elasticsearch\Client                    $elasticsearch
 * @property-read \ManaPHP\MailerInterface                 $mailer
 * @property-read \ManaPHP\Ipc\CacheInterface              $ipcCache
 * @property-read \ManaPHP\Bos\ClientInterface             $bosClient
 * @property-read \ManaPHP\Pool\ManagerInterface           $poolManager
 * @property-read \ManaPHP\ValidatorInterface              $validator
 * @property-read \ManaPHP\I18n\TranslatorInterface        $translator
 * @property-read \ManaPHP\WebSocket\PusherInterface       $wsPusher
 * @property-read \ManaPHP\CoroutineInterface              $coroutine
 * @property-read \ManaPHP\WebSocket\ClientInterface       $wsClient
 * @property \object                                       $_context
 */
class Component implements ComponentInterface, JsonSerializable
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
     * @return object
     */
    public function createContext()
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
                throw new Exception(['`:context` context class is not exists', 'context' => get_class($this) . 'Context']);
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
                            return $context[$object_id] = $this->createContext();
                        }

                        $parent_context = Coroutine::getContext($parent_cid);
                        if ($object = $parent_context[$object_id] ?? null) {
                            return $context[$object_id] = $object instanceof Inseparable ? $this->createContext() : $object;
                        } else {
                            $object = $context[$object_id] = $this->createContext();
                            if (!$object instanceof Inseparable) {
                                $parent_context[$object_id] = $object;
                            }
                        }
                    }
                    return $object;
                } else {
                    if (!$object = $__root_context[$object_id] ?? null) {
                        return $__root_context[$object_id] = $this->createContext();
                    } else {
                        return $object;
                    }
                }
            } elseif (PHP_SAPI === 'cli') {
                if (!$object = $__root_context[$object_id] ?? null) {
                    $__root_context[] = $this;
                    return $this->_context = $this->createContext();
                } else {
                    return $object;
                }
            } else {
                return $this->_context = $this->createContext();
            }
        }

        if ($this->_di === null) {
            $this->_di = Di::getDefault();
        }

        return $this->{$name} = $this->_di->{$name};
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
        if ($name === 'di') {
            return true;
        }

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
     * Fires an event in the events manager causing that the active listeners will be notified about it
     *
     * @param string $event
     * @param array  $data
     *
     * @return void
     */
    public function fireEvent($event, $data = [])
    {
        $this->eventsManager->fireEvent($event, $this, $data);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $data = [];
        foreach (get_object_vars($this) as $k => $v) {
            if ($k === '_di' || $k === '_object_id' || $k === 'eventsManager' || $v instanceof self) {
                continue;
            }

            $data[$k] = $v;
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