<?php
namespace ManaPHP;

use ManaPHP\Cache\AdapterInterface;

/**
 * Class ManaPHP\Cache
 *
 * @package ManaPHP
 *
 * @property \ManaPHP\Serializer\AdapterInterface $serializer
 */
class Cache extends Component implements CacheInterface
{
    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * @var \ManaPHP\Cache\AdapterInterface
     */
    public $adapter;

    /**
     * Cache constructor.
     *
     * @param string|array|\ManaPHP\Cache\AdapterInterface $options
     */
    public function __construct($options = [])
    {
        if ($options instanceof AdapterInterface || is_string($options)) {
            $options = ['adapter' => $options];
        } elseif (is_object($options)) {
            $options = (array)$options;
        }

        $this->adapter = $options['adapter'];

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

        if (!is_object($this->adapter)) {
            $this->adapter = $this->_dependencyInjector->getShared($this->adapter);
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
        $data = $this->adapter->get($this->_prefix . $key);
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
        $this->adapter->set($this->_prefix . $key, $this->serializer->serialize($value), $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->adapter->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->adapter->exists($this->_prefix . $key);
    }
}