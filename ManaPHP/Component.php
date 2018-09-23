<?php
namespace ManaPHP;

/**
 * Class ManaPHP\Component
 *
 * @package component
 *
 * @property \ManaPHP\AliasInterface                  $alias
 * @property \ManaPHP\Event\ManagerInterface          $eventsManager
 * @property \ManaPHP\FilesystemInterface             $filesystem
 * @property \ManaPHP\LoggerInterface                 $logger
 * @property \ManaPHP\Configuration\Configure         $configure
 * @property \ManaPHP\Configuration\SettingsInterface $settings
 * @property \ManaPHP\Security\CryptInterface         $crypt
 * @property \ManaPHP\IdentityInterface               $identity
 * @property \ManaPHP\Loader                          $loader
 * @property \ManaPHP\CacheInterface                  $cache
 * @property \ManaPHP\CacheInterface                  $redisCache
 * @property \ManaPHP\Security\RandomInterface        $random
 * @property \ManaPHP\Curl\EasyInterface              $httpClient
 * @property \ManaPHP\DbInterface                     $db
 * @property \Redis                                   $redis
 * @property \ManaPHP\Mongodb                         $mongodb
 * @property \ManaPHP\AmqpInterface                   $rabbitmq
 * @property \Elasticsearch\Client                    $elasticsearch
 * @property \ManaPHP\ZookeeperInterface              $zookeeper
 * @property \ManaPHP\MailerInterface                 $mailer
 * @property \ManaPHP\Task\ManagerInterface           $tasksManager
 */
class Component implements ComponentInterface, \JsonSerializable
{
    /**
     * @var bool
     */
    protected $_traced;

    /**
     * @var \ManaPHP\Di
     */
    protected $_di;

    /**
     * @return array|bool
     */
    public function saveInstanceState()
    {
        return false;
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function restoreInstanceState($data)
    {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * Sets the dependency injector
     *
     * @param \ManaPHP\DiInterface $di
     *
     * @return static
     */
    public function setDi($di)
    {
        $this->_di = $di;

        return $this;
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
     * @param string $name
     *
     * @return array
     */
    public function getConstants($name)
    {
        $name = strtoupper($name) . '_';
        $constants = [];
        $rc = new \ReflectionClass($this);

        foreach ($rc->getConstants() as $cName => $cValue) {
            if (strpos($cName, $name) === 0) {
                $constants[$cValue] = strtolower(substr($cName, strlen($name)));
            }
        }

        return $constants;
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
            $this->fireEvent('component:setUndefinedProperty', ['name' => $name, 'class' => get_called_class()]);
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
     *
     * @return static
     */
    public function attachEvent($event, $handler = null)
    {
        $this->eventsManager->attachEvent($event, $handler ?: $this);

        return $this;
    }

    /**
     * Fires an event in the events manager causing that the active listeners will be notified about it
     *
     * @param string $event
     * @param array  $data
     *
     * @return bool|null
     */
    public function fireEvent($event, $data = [])
    {
        return $this->eventsManager->fireEvent($event, $this, $data);
    }

    /**
     * @param string $event
     * @param string $handler
     */
    public static function peekEvent($event, $handler)
    {
        if (strpos($event, ':') === false) {
            $type = $className = get_called_class();
            while (true) {
                if (strpos($className, 'ManaPHP\\') === 0) {
                    $type = lcfirst(substr($className, strrpos($className, '\\') + 1));
                    break;
                } else {
                    $className = get_parent_class($className);
                }
            }
            $event = "$type:$event";
        }

        Di::getDefault()->eventsManager->peekEvent($event, $handler);
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

            $data[$k] = $v;
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
            if ($v === null) {
                continue;
            }

            if (is_scalar($v) || $v === null) {
                $data[$k] = $v;
            } elseif (is_array($v)) {
                $isPlain = true;

                foreach ($v as $vv) {
                    if (!is_scalar($vv) && $vv !== null) {
                        $isPlain = false;
                        break;
                    }
                }

                if ($isPlain) {
                    $data[$k] = $v;
                }
            }
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