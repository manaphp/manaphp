<?php
namespace ManaPHP\Counter\Adapter {

    use ManaPHP\Counter;

    class Redis extends Counter
    {
        protected $_prefix;

        public function __construct($prefix = 'manaphp:counter:')
        {
            $this->_prefix = $prefix;
        }

        /**
         * @param string|array $key
         *
         * @return array
         */
        protected function _getKey($key)
        {
            if (is_string($key)) {
                return [$this->_prefix . 'mixed', $key];
            } else {
                list($key, $hashKey) = $key;
                return [$this->_prefix . $key, $hashKey];
            }
        }

        public function _get($key)
        {
            list($key, $hashKey) = $this->_getKey($key);

            return $this->redis->hGet($key, $hashKey);
        }

        public function _increment($key, $step)
        {
            list($key, $hashKey) = $this->_getKey($key);

            return $this->redis->hIncrBy($key, $hashKey, $step);
        }

        public function _delete($key)
        {
            list($key, $hashKey) = $this->_getKey($key);

            $this->redis->hDel($key, $hashKey, $key);
        }
    }
}