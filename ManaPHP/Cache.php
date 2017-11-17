<?php
namespace ManaPHP;

use ManaPHP\Component\ScopedCloneableInterface;

/**
 * Class ManaPHP\Cache
 *
 * @package cache
 *
 * @property \ManaPHP\Serializer\AdapterInterface $serializer
 */
class Cache extends Component implements CacheInterface, ScopedCloneableInterface
{
    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * @var \ManaPHP\Cache\EngineInterface
     */
    protected $_engine;

    /**
     * Cache constructor.
     *
     * @param string|array|\ManaPHP\Cache\EngineInterface $options
     */
    public function __construct($options = [])
    {
        if (is_string($options) || is_object($options)) {
            $options = ['engine' => $options];
        }

        $this->_engine = $options['engine'];

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        if (!is_object($this->_engine)) {
            $this->_engine = $this->_dependencyInjector->getShared($this->_engine);
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key)
    {
        $data = $this->_engine->get($this->_prefix . $key);
        if ($data === false) {
            return false;
        } else {
            return $this->serializer->deserialize($data);
        }
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return void
     */
    public function set($key, $value, $ttl)
    {
        $this->_engine->set($this->_prefix . $key, $this->serializer->serialize($value), $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->_engine->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->_engine->exists($this->_prefix . $key);
    }

    /**
     * @param string   $key
     * @param int      $ttl
     * @param callable $callback
     *
     * @return mixed
     */
    public function remember($key, $ttl, $callback)
    {
        $r = $this->get($key);
        if ($r === false) {
            $r = $callback();
            $this->set($key, $r, $ttl);
        }

        return $r;
    }

    /**
     * @param \ManaPHP\Component $scope
     *
     * @return static
     */
    public function getScopedClone($scope)
    {
        $cloned = clone $this;
        $cloned->_prefix = ($this->_prefix ? $this->_prefix . ':' : '') . $scope->getComponentName($this) . ':';

        return $cloned;
    }
}