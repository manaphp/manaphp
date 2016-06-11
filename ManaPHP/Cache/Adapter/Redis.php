<?php

namespace ManaPHP\Cache\Adapter {

    use ManaPHP\Cache;
    use ManaPHP\Di;

    class Redis extends Cache
    {
        /**
         * Fetch content
         *
         * @param string $key
         *
         * @return string|false
         * @throws \ManaPHP\Cache\Adapter\Exception
         */
        public function _get($key)
        {
            return $this->redis->get($key);
        }

        /**
         * Caches content
         *
         * @param string $key
         * @param string $value
         * @param int    $ttl
         *
         * @return void
         * @throws \ManaPHP\Cache\Adapter\Exception
         */
        public function _set($key, $value, $ttl)
        {
            $this->redis->set($key, $value, $ttl);
        }

        /**
         * Delete content
         *
         * @param string $key
         *
         * @void
         * @throws \ManaPHP\Cache\Adapter\Exception
         */
        public function _delete($key)
        {
            $this->redis->delete($key);
        }

        /**
         * Check if key exists
         *
         * @param string $key
         *
         * @return bool
         * @throws \ManaPHP\Cache\Adapter\Exception
         */
        public function _exists($key)
        {
            return $this->redis->exists($key);
        }
    }
}