<?php

namespace ManaPHP\Store\Adapter {

    use ManaPHP\Component;
    use ManaPHP\Di;
    use ManaPHP\Store\AdapterInterface;

    class Redis extends Component implements AdapterInterface
    {
        /**
         * @var array
         */
        protected $_options;

        /**
         * @var string
         */
        protected $key = 'store:';

        public function __construct($dependencyInjector = null)
        {
            $this->_dependencyInjector = $dependencyInjector ?: Di::getDefault();
        }

        /**
         * Fetch content
         *
         * @param string $id
         *
         * @return string|false
         * @throws \ManaPHP\Store\Adapter\Exception
         */
        public function get($id)
        {
            return $this->redis->hGet($this->key, $id);
        }

        public function mGet($ids)
        {
            return $this->redis->hMGet($this->key, $ids);
        }

        /**
         * Caches content
         *
         * @param string $id
         * @param string $value
         *
         * @return void
         * @throws \ManaPHP\Store\Adapter\Exception
         */
        public function set($id, $value)
        {
            $this->redis->hSet($this->key, $id, $value);
        }

        public function mSet($idValues)
        {
            $this->redis->hMset($this->key, $idValues);
        }

        /**
         * Delete content
         *
         * @param string $id
         *
         * @void
         * @throws \ManaPHP\Store\Adapter\Exception
         */
        public function delete($id)
        {
            $this->redis->hDel($this->key, $id);
        }

        /**
         * Check if id exists
         *
         * @param string $id
         *
         * @return bool
         * @throws \ManaPHP\Store\Adapter\Exception
         */
        public function exists($id)
        {
            return $this->redis->hExists($this->key, $id);
        }
    }
}