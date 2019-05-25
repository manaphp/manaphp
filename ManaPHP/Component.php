<?php
namespace ManaPHP;

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
 * @property-read \ManaPHP\Task\ManagerInterface           $tasksManager
 * @property-read \ManaPHP\Ipc\CacheInterface              $ipcCache
 * @property-read \ManaPHP\Bos\ClientInterface             $bosClient
 * @property-read \ManaPHP\Pool\ManagerInterface           $poolManager
 * @property-read \ManaPHP\ValidatorInterface              $validator
 * @property \object                                       $_context
 */
class Component implements ComponentInterface, \JsonSerializable
{
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
     * Magic method __get
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === '_context') {
            if (PHP_SAPI === 'cli') {
                return ContextManager::get($this);
            } else {
                if (!$context_class = $this->getContextClass()) {
                    throw new Exception(['`:context` context class is not exists', 'context' => static::class . 'Context']);
                }
                return $this->_context = new $context_class();
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
        if (is_scalar($value)) {
            $this->eventsManager->fireEvent('component:setUndefinedProperty', $this, ['name' => $name, 'class' => static::class]);
        }

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
            if ($k === '_di' && ($v === null || $v === Di::getDefault())) {
                continue;
            }

            if ($k === '_context' && $v === null) {
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

    /**
     * @return string|null
     */
    public function getContextClass()
    {
        static $cached = [];

        $class = static::class;
        if (!isset($cached[$class])) {
            $context = null;
            $parent = $class;
            do {
                $try = $parent . 'Context';
                if (class_exists($try, false)) {
                    $context = $try;
                    break;
                }
            } while ($parent = get_parent_class($parent));

            return $cached[$class] = $context;
        }

        return $cached[$class];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}