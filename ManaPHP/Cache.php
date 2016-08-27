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
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key)
    {
        $data = $this->cacheEngine->get($key);
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
            $data = $this->cacheEngine->get($key);
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
        $this->cacheEngine->set($key, $this->serializer->serialize($value), $ttl);
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
            $this->cacheEngine->set($key, $this->serializer->serialize($value), $ttl);
        }
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->cacheEngine->delete($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->cacheEngine->exists($key);
    }
}