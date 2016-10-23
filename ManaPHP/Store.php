<?php
namespace ManaPHP;

use ManaPHP\Store\AdapterInterface;

/**
 * Class ManaPHP\Store
 *
 * @package ManaPHP
 *
 * @property \ManaPHP\Serializer\AdapterInterface $serializer
 */
class Store extends Component implements StoreInterface
{
    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * @var \ManaPHP\Store\AdapterInterface
     */
    public $adapter;

    /**
     * Store constructor.
     *
     * @param string|array|\ManaPHP\Store\AdapterInterface $options
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
            $this->_dependencyInjector->getShared($this->adapter);
        }

        return $this;
    }

    /**
     * Fetch content
     *
     * @param string $key
     *
     * @return mixed
     * @throws \ManaPHP\Store\Exception
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
     * Stores content
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set($key, $value)
    {
        $this->adapter->set($this->_prefix . $key, $this->serializer->serialize($value));
    }

    /**
     * Delete content
     *
     * @param string $key
     *
     * @void
     */
    public function delete($key)
    {
        $this->adapter->delete($this->_prefix . $key);
    }

    /**
     * Check if id exists
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->adapter->exists($this->_prefix . $key);
    }
}