<?php
namespace ManaPHP {

    use ManaPHP\Cache\AdapterInterface;
    use ManaPHP\Di;

    abstract class Cache extends Component implements CacheInterface, AdapterInterface
    {
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
            $data = $this->_get($key);
            if ($data === false) {
                return false;
            } else {
                return $this->serializer->deserialize($data);
            }
        }

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
         * Caches content
         *
         * @param string $key
         * @param mixed  $value
         * @param int    $ttl
         *
         * @return void
         * @throws \ManaPHP\Cache\Exception
         */
        public function set($key, $value, $ttl)
        {
            $this->_set($key, $this->serializer->serialize($value), $ttl);
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
                $this->_set($key, $this->serializer->serialize($value), $ttl);
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
            $this->_delete($key);
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
                $this->_delete($key);
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
            return $this->_exists($key);
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