<?php
namespace ManaPHP;

/**
 * Class Store
 *
 * @package ManaPHP
 *
 * @property \ManaPHP\Serializer\AdapterInterface $serializer
 * @property \ManaPHP\Store\EngineInterface       $storeEngine
 */
class Store extends Component implements StoreInterface
{
    /**
     * @var string
     */
    protected $_prefix;

    /**
     * Store constructor.
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
     * Fetch content
     *
     * @param string $key
     *
     * @return mixed
     * @throws \ManaPHP\Store\Exception
     */
    public function get($key)
    {
        $data = $this->storeEngine->get($this->_prefix . $key);
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
        $this->storeEngine->set($this->_prefix . $key, $this->serializer->serialize($value));
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
        $this->storeEngine->delete($this->_prefix . $key);
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
        return $this->storeEngine->exists($this->_prefix . $key);
    }
}