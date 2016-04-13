<?php

namespace ManaPHP\Caching\Store\Adapter {

    use ManaPHP\Caching\Store\AdapterInterface;

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
         * @var string
         */
        protected $key = '';

        /**
         * Redis constructor.
         *
         * @param array
         *
         * @throws \ManaPHP\Caching\Store\Exception
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
         * @throws \ManaPHP\Caching\Store\Exception
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
         * @param string $id
         *
         * @return string|false
         * @throws \ManaPHP\Caching\Store\Exception
         */
        public function get($id)
        {
            if ($this->_redis === null) {
                $this->_connect();
            }

            return $this->_redis->hGet($this->key, $id);
        }

        public function mGet($ids)
        {
            if ($this->_redis === null) {
                $this->_connect();
            }

            return $this->_redis->hMGet($this->key, $ids);
        }

        /**
         * Caches content
         *
         * @param string $id
         * @param string $value
         *
         * @return void
         * @throws \ManaPHP\Caching\Store\Exception
         */
        public function set($id, $value)
        {
            if ($this->_redis === null) {
                $this->_connect();
            }

            $this->_redis->hSet($this->key, $id, $value);
        }

        public function mSet($idValues)
        {
            if ($this->_redis === null) {
                $this->_connect();
            }

            $this->_redis->hMset($this->key, $idValues);
        }

        /**
         * Delete content
         *
         * @param string $id
         *
         * @void
         * @throws \ManaPHP\Caching\Store\Exception
         */
        public function delete($id)
        {
            if ($this->_redis === null) {
                $this->_connect();
            }

            $this->_redis->hDel($this->key, $id);
        }

        /**
         * Check if id exists
         *
         * @param string $id
         *
         * @return bool
         * @throws \ManaPHP\Caching\Store\Exception
         */
        public function exists($id)
        {
            if ($this->_redis === null) {
                $this->_connect();
            }

            return $this->_redis->hExists($this->key, $id);
        }
    }
}