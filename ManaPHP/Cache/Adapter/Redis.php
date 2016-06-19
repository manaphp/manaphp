<?php

namespace ManaPHP\Cache\Adapter {

    use ManaPHP\Cache;
    use ManaPHP\Di;

    class Redis extends Cache
    {
        protected $_prefix = '_manaphp_:cache:';

        /**
         * Redis constructor.
         *
         * @param string|array $options
         */
        public function __construct($options = [])
        {
            if (is_string($options)) {
                $options['prefix'] = $options;
            }

            if (isset($options['prefix'])) {
                $this->_prefix .= $options['prefix'];
            }
        }

        public function _get($key)
        {
            return $this->redis->get($this->_prefix . $key);
        }

        public function _set($key, $value, $ttl)
        {
            $this->redis->set($this->_prefix . $key, $value, $ttl);
        }

        public function _delete($key)
        {
            $this->redis->delete($this->_prefix . $key);
        }

        public function _exists($key)
        {
            return $this->redis->exists($this->_prefix . $key);
        }
    }
}