<?php

namespace ManaPHP\Cache\Adapter {

    use ManaPHP\Cache;
    use ManaPHP\Di;

    class Redis extends Cache
    {
       
        public function _get($key)
        {
            return $this->redis->get($key);
        }

        public function _set($key, $value, $ttl)
        {
            $this->redis->set($key, $value, $ttl);
        }

        public function _delete($key)
        {
            $this->redis->delete($key);
        }

        public function _exists($key)
        {
            return $this->redis->exists($key);
        }
    }
}