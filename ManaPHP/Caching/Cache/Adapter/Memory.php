<?php
namespace ManaPHP\Caching\Cache\Adapter {

    use ManaPHP\Caching\Cache\AdapterInterface;

    class Memory implements AdapterInterface
    {
        /**
         * @var array
         */
        protected $_data = [];

        public function get($key)
        {
            if (isset($this->_data[$key])) {
                if ($this->_data[$key]['deadline'] >= time()) {
                    return $this->_data[$key]['data'];
                } else {
                    unset($this->_data[$key]);

                    return false;
                }
            } else {
                return false;
            }
        }

        public function set($key, $value, $ttl)
        {
            $this->_data[$key] = ['deadline' => time() + $ttl, 'data' => $value];
        }

        public function delete($key)
        {
            unset($this->_data[$key]);
        }

        public function exists($key)
        {
            return isset($this->_data[$key]) && $this->_data[$key]['deadline'] >= time();
        }
    }
}