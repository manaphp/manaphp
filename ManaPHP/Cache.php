<?php
namespace ManaPHP;

/**
 * Class Cache
 *
 * @package ManaPHP
 *
 * @property \ManaPHP\Serializer\AdapterInterface $serializer
 * @property \ManaPHP\Cache\EngineInterface       $cacheEngine
 */
class Cache extends Component implements CacheInterface
{
    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * Cache constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        } elseif (is_string($options)) {
            $options = ['prefix' => $options];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key)
    {
        $data = $this->cacheEngine->get($this->_prefix . $key);
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
        $this->cacheEngine->set($this->_prefix . $key, $this->serializer->serialize($value), $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->cacheEngine->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->cacheEngine->exists($this->_prefix . $key);
    }
}