<?php
namespace ManaPHP\Caching {

    interface CacheInterface
    {
        /**
         * Checks whether a specified key exists in the cache.
         *
         * @param string $key
         *
         * @return bool
         */
        public function exists($key);

        /**
         * Retrieves a value from cache with a specified key.
         *
         * @param string $key
         *
         * @return mixed|false
         */
        public function get($key);

        /**
         * Stores a value identified by a key into cache.
         *
         * @param string $key
         * @param mixed  $value
         * @param int    $ttl
         *
         * @return void
         */
        public function set($key, $value, $ttl = null);

        /**
         * Deletes a value with the specified key from cache
         *
         * @param string $key
         *
         * @void
         */
        public function delete($key);

        /** Retrieves the internal adapter instance
         *
         * @return \ManaPHP\Caching\Cache\AdapterInterface
         */
        public function getAdapter();

        /**
         * @return \ManaPHP\Caching\Serializer\AdapterInterface
         */
        public function getSerializer();
    }
}