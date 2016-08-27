<?php
namespace ManaPHP;

use ManaPHP\Cache\AdapterInterface;

/**
 * Class Cache
 *
 * @package ManaPHP
 *
 * @property \ManaPHP\Serializer\AdapterInterface $serializer
 */
abstract class Cache extends Component implements CacheInterface, AdapterInterface
{
    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key)
    {
        $data = $this->_get($key);
        if ($data === false) {
            return false;
        } else {
            return $this->serializer->deserialize($data);
        }
    }

    /**
     * @param array $keys
     *
     * @return array
     */
    public function mGet($keys)
    {
        $keyValues = [];
        foreach ($keys as $key) {
            $data = $this->_get($key);
            if ($data !== false) {
                $data = $this->serializer->deserialize($data);
            }
            $keyValues[$key] = $data;
        }

        return $keyValues;
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
        $this->_set($key, $this->serializer->serialize($value), $ttl);
    }

    /**
     * @param array $keyValues
     * @param int   $ttl
     *
     * @return void
     */
    public function mSet($keyValues, $ttl = null)
    {
        foreach ($keyValues as $key => $value) {
            $this->_set($key, $this->serializer->serialize($value), $ttl);
        }
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->_delete($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->_exists($key);
    }
}