<?php

namespace ManaPHP\Cache\Adapter {

    use ManaPHP\Cache\AdapterInterface;
    use ManaPHP\Component;
    use ManaPHP\Di;

    class Redis extends Component implements AdapterInterface
    {
        public function __construct($dependencyInjector = null)
        {
            $this->_dependencyInjector = $dependencyInjector ?: Di::getDefault();
        }

        /**
         * Fetch content
         *
         * @param string $key
         *
         * @return string|false
         * @throws \ManaPHP\Cache\Adapter\Exception
         */
        public function get($key)
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
        public function set($key, $value, $ttl)
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
        public function delete($key)
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
        public function exists($key)
        {
            return $this->redis->exists($key);
        }
    }
}