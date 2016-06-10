<?php
namespace ManaPHP {

    use ManaPHP\Serializer\Adapter\JsonPhp;
    use ManaPHP\Di;

    class Cache implements CacheInterface
    {
        /**
         * @var \ManaPHP\Cache\AdapterInterface
         */
        protected $_adapter;

        /**
         * @var string
         */
        protected $_prefix;

        /**
         * @var int
         */
        protected $_ttl;

        /**
         * @var \ManaPHP\Serializer\AdapterInterface;
         */
        protected $_serializer;

        /**
         * Cache constructor.
         *
         * @param string                                 $prefix
         * @param int                                    $ttl
         * @param string|\ManaPHP\Cache\AdapterInterface $adapter
         *
         * @throws \ManaPHP\Cache\Exception|\ManaPHP\Di\Exception
         */
        public function __construct($prefix = '', $ttl = -1, $adapter = null)
        {
            $this->_prefix = $prefix;
            $this->_ttl = $ttl;

            if ($adapter === null) {
                $adapter = 'defaultCacheAdapter';
            }

            $this->_adapter = is_string($adapter) ? Di::getDefault()->getShared($adapter) : $adapter;
            $this->_serializer = new JsonPhp();
        }

        /**
         * Fetch content
         *
         * @param string $key
         *
         * @return mixed
         * @throws \ManaPHP\Cache\Exception
         */
        public function get($key)
        {
            $data = $this->_adapter->get($this->_prefix . $key);
            if ($data === false) {
                return false;
            } else {
                return $this->_serializer->deserialize($data);
            }
        }

        public function mGet($keys)
        {
            $keyValues = [];
            foreach ($keys as $key) {
                $data = $this->_adapter->get($this->_prefix . $key);
                if ($data !== false) {
                    $data = $this->_serializer->deserialize($data);
                }
                $keyValues[$key] = $data;
            }

            return $keyValues;
        }

        /**
         * Caches content
         *
         * @param string $key
         * @param mixed  $value
         * @param int    $ttl
         *
         * @return void
         * @throws \ManaPHP\Cache\Exception
         */
        public function set($key, $value, $ttl = null)
        {
            $this->_adapter->set($this->_prefix . $key, $this->_serializer->serialize($value), $ttl);
        }

        /**
         * Stores  values identified by  keys into cache.
         *
         * @param array $keyValues
         * @param int   $ttl
         *
         * @return void
         */
        public function mSet($keyValues, $ttl = null)
        {
            foreach ($keyValues as $key => $value) {
                $this->_adapter->set($this->_prefix . $key, $this->_serializer->serialize($value), $ttl);
            }
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
            $this->_adapter->delete($this->_prefix . $key);
        }

        /**
         * Deletes values with the specified keys from cache
         *
         * @param array $keys
         *
         * @void
         */
        public function mDelete($keys)
        {
            foreach ($keys as $key) {
                $this->_adapter->delete($this->_prefix . $key);
            }
        }

        /**
         * Check if key exists
         *
         * @param string $key
         *
         * @return bool
         */
        public function exists($key)
        {
            return $this->_adapter->exists($this->_prefix . $key);
        }

        /**
         * @return array
         */
        public function __debugInfo()
        {
            return get_object_vars($this) ?: [];
        }
    }
}