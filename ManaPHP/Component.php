<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2016/1/18
 */

namespace ManaPHP;

/**
 * Class ManaPHP\Component
 *
 * @package component
 *
 * @property \ManaPHP\AliasInterface          $alias
 * @property \ManaPHP\Event\ManagerInterface  $eventsManager
 * @property \ManaPHP\FilesystemInterface     $filesystem
 * @property \ManaPHP\LoggerInterface         $logger
 * @property \Application\Configure           $configure
 * @property \ManaPHP\Security\CryptInterface $crypt
 */
class Component implements ComponentInterface
{
    /**
     * @var \ManaPHP\Di
     */
    protected $_dependencyInjector;

    /**
     * Sets the dependency injector
     *
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;

        return $this;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \ManaPHP\Di
     */
    public function getDependencyInjector()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Magic method __get
     *
     * @param string $name
     *
     * @return mixed
     * @throws \ManaPHP\Di\Exception
     * @throws \ManaPHP\Component\Exception
     */
    public function __get($name)
    {
        if ($this->_dependencyInjector === null) {
            $this->_dependencyInjector = Di::getDefault();
        }

        if ($name === 'persistent') {
            $getParameter = [get_class($this), $this->_dependencyInjector];
            $this->{'persistent'} = $this->_dependencyInjector->get('sessionBag', $getParameter);
        } else {
            $this->{$name} = $this->_dependencyInjector->{$name};
            if ($this->{$name} === null) {
                throw new \ManaPHP\Di\Exception('Access to undefined property `:name` of `:class`', ['name' => $name, 'class' => get_called_class()]);
            }
        }

        return $this->{$name};
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
        if ($name === 'di' || $name === 'persistent') {
            return true;
        }

        if ($this->_dependencyInjector === null) {
            $this->_dependencyInjector = Di::getDefault();
        }

        return $this->_dependencyInjector->has($name);
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
     * @return array
     */
    public function __debugInfo()
    {
        $defaultDi = Di::getDefault();

        $data = [];
        foreach (get_object_vars($this) as $k => $v) {

            if ($v === $defaultDi) {
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
}