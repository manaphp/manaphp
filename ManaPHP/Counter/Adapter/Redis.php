<?php
namespace ManaPHP\Counter\Adapter {

    use ManaPHP\Counter;

    class Redis extends Counter
    {
        protected $_key;

        public function __construct($prefix = 'manaphp:counter')
        {
            $this->_key = $prefix;
        }

        public function _increment($key, $step)
        {
            return $this->redis->hIncrBy($this->_key, $key, $step);
        }

        public function _delete($key)
        {
            $this->redis->hDel($this->_key, $key);
        }
    }
}