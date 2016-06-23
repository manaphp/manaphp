<?php
namespace ManaPHP {

    use ManaPHP\Cache\AdapterInterface;
    use ManaPHP\Di;
    use ManaPHP\Utility\Text;

    abstract class Cache extends Component implements CacheInterface, AdapterInterface
    {
        protected function _formatKey($key)
        {
            if ($key[0] === '!') {
                return $key;
            }

            if (Text::contains($key, '/')) {
                $parts = explode('/', $key, 2);
                return $parts[0] . '/' . md5($parts[1]);
            } else {
                return $key;
            }
        }

        public function get($key)
        {
            $data = $this->_get($this->_formatKey($key));
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
                $data = $this->_get($this->_formatKey($key));
                if ($data !== false) {
                    $data = $this->serializer->deserialize($data);
                }
                $keyValues[$key] = $data;
            }

            return $keyValues;
        }

        public function set($key, $value, $ttl)
        {
            $this->_set($this->_formatKey($key), $this->serializer->serialize($value), $ttl);
        }

        public function mSet($keyValues, $ttl = null)
        {
            foreach ($keyValues as $key => $value) {
                $this->_set($this->_formatKey($key), $this->serializer->serialize($value), $ttl);
            }
        }

        public function delete($key)
        {
            $this->_delete($this->_formatKey($key));
        }

        public function mDelete($keys)
        {
            foreach ($keys as $key) {
                $this->_delete($this->_formatKey($key));
            }
        }

        public function exists($key)
        {
            return $this->_exists($this->_formatKey($key));
        }
    }
}