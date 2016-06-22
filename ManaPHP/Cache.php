<?php
namespace ManaPHP {

    use ManaPHP\Cache\AdapterInterface;
    use ManaPHP\Di;

    abstract class Cache extends Component implements CacheInterface, AdapterInterface
    {
        public function get($key)
        {
            $data = $this->_get($key);
            if ($data === false) {
                return false;
            } else {
                return $this->serializer->deserialize($data);
            }
        }

        public function mGet($keys)
        {
            $keyValues = [];
            foreach ($keys as $key) {
                $data = $this->_get($key);
                if ($data !== false) {
                    $data = $this->serializer->deserialize($data);
                }
                $keyValues[$key] = $data;
            }

            return $keyValues;
        }

        public function set($key, $value, $ttl)
        {
            $this->_set($key, $this->serializer->serialize($value), $ttl);
        }

        public function mSet($keyValues, $ttl = null)
        {
            foreach ($keyValues as $key => $value) {
                $this->_set($key, $this->serializer->serialize($value), $ttl);
            }
        }

        public function delete($key)
        {
            $this->_delete($key);
        }

        public function mDelete($keys)
        {
            foreach ($keys as $key) {
                $this->_delete($key);
            }
        }

        public function exists($key)
        {
            return $this->_exists($key);
        }
    }
}