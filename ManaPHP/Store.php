<?php
namespace ManaPHP;

use ManaPHP\Store\AdapterInterface;
use ManaPHP\Utility\Text;

abstract class Store extends Component implements StoreInterface, AdapterInterface
{
    /**
     * @param string $key
     *
     * @return string
     */
    protected function _formatKey($key)
    {
        if ($key[0] === '!') {
            return $key;
        }

        if (Text::contains($key, '/')) {
            $parts = explode('/', $key, 2);
            return $parts[0] . '/' . md5($parts[1]);
        } else {
            return $key;
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
        $content = $this->_get($this->_formatKey($key));
        if ($content === false) {
            return false;
        } else {
            return $this->serializer->deserialize($content);
        }
    }

    /**
     * Retrieves a value from store with a specified keys.
     *
     * @param array $keys
     *
     * @return array
     * @throws \ManaPHP\Store\Exception
     */
    public function mGet($keys)
    {
        $keyValues = [];
        foreach ($keys as $key) {
            $value = $this->_get($this->_formatKey($key));
            if ($value === false) {
                $keyValues[$key] = $value;
            } else {
                $keyValues[$key] = $this->serializer->deserialize($value);
            }
        }

        return $keyValues;
    }

    /**
     * Stores content
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     * @throws \ManaPHP\Cache\Exception
     */
    public function set($key, $value)
    {
        $this->_set($this->_formatKey($key), $this->serializer->serialize($value));
    }

    /**
     * Stores a value identified by a id into store.
     *
     * @param array $keyValues
     *
     * @return void
     */
    public function mSet($keyValues)
    {
        $completeKeyValues = [];
        foreach ($keyValues as $key => $value) {
            $completeKey = $this->_formatKey($key);
            $completeKeyValues[$completeKey] = $this->serializer->serialize($value);
        }

        $this->_mSet($completeKeyValues);
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
        $this->_delete($this->_formatKey($key));
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
        return $this->_exists($this->_formatKey($key));
    }
}