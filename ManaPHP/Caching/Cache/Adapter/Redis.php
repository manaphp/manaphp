<?php

namespace ManaPHP\Caching\Cache\Adapter {

    use ManaPHP\Caching\Cache\AdapterInterface;

    class Redis implements AdapterInterface
    {

        /**
         * @var array
         */
        protected $_options;
        /**
         * @var \Redis
         */
        protected $_redis = null;

        /**
         * Redis constructor.
         *
         * @param array $options
         *
         * @throws \ManaPHP\Caching\Cache\Exception
         */
        public function __construct($options)
        {
            if (is_object($options)) {
                $options = (array)$options;
            }

            if (!extension_loaded('redis')) {
                throw new Exception('Redis is not installed, or the extension is not loaded');
            }

            if (!isset($options['host'])) {
                $options['host'] = '127.0.0.1';
            }

            if (!isset($options['port'])) {
                $options['port'] = 6379;
            }

            if (!isset($options['db'])) {
                $options['db'] = 0;
            }

            if (!isset($options['persistent'])) {
                $options['persistent'] = false;
            }

            if (!isset($options['timeout'])) {
                $options['timeout'] = 0.0;
            }

            $this->_options = $options;
        }

        /**
         * @throws \ManaPHP\Caching\Cache\Exception
         */
        protected function _connect()
        {
            $options = $this->_options;

            $host = $options['host'];
            $port = $options['port'];
            $timeout = $options['timeout'];

            $redis = new \Redis();

            if ($options['persistent']) {
                $ret = $redis->pconnect($host, $port, $timeout);
            } else {
                $ret = $redis->connect($host, $port, $timeout);
            }

            if (!$ret) {
                throw new Exception('Could not connect to the Redis server: ' . $host . ':' . $port);
            }

            if (isset($options['auth']) && !$redis->auth($options['auth'])) {
                throw new Exception('Failed to authenticate with the Redis server');
            }

            if (isset($options['db']) && !$redis->select($options['db'])) {
                throw new Exception('Redis server selected database failed: ' . $options['db']);
            }

            $this->_redis = $redis;
        }

        /**
         * Fetch content
         *
         * @param string $key
         *
         * @return string|false
         * @throws \ManaPHP\Caching\Cache\Exception
         */
        public function get($key)
        {
            if ($this->_redis === null) {
                $this->_connect();
            }

            return $this->_redis->get($key);
        }

        /**
         * Caches content
         *
         * @param string $key
         * @param string $value
         * @param int    $ttl
         *
         * @return void
         * @throws \ManaPHP\Caching\Cache\Exception
         */
        public function set($key, $value, $ttl)
        {
            if ($this->_redis === null) {
                $this->_connect();
            }

            $this->_redis->set($key, $value, $ttl);
        }

        /**
         * Delete content
         *
         * @param string $key
         *
         * @void
         * @throws \ManaPHP\Caching\Cache\Exception
         */
        public function delete($key)
        {
            if ($this->_redis === null) {
                $this->_connect();
            }

            $this->_redis->delete($key);
        }

        /**
         * Check if key exists
         *
         * @param string $key
         *
         * @return bool
         * @throws \ManaPHP\Caching\Cache\Exception
         */
        public function exists($key)
        {
            if ($this->_redis === null) {
                $this->_connect();
            }

            return $this->_redis->exists($key);
        }
    }
}