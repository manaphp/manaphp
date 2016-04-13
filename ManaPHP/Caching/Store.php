<?php
namespace ManaPHP\Caching {

    use ManaPHP\Caching\Serializer\Adapter\JsonPhp;
    use ManaPHP\Di;

    class Store implements StoreInterface
    {
        /**
         * @var \ManaPHP\Caching\Store\AdapterInterface
         */
        protected $_adapter;

        /**
         * @var string
         */
        protected $_prefix;

        /**
         * @var \ManaPHP\Caching\Serializer\AdapterInterface $_serializer
         */
        protected $_serializer;

        /**
         * Store constructor.
         *
         * @param string                                         $prefix
         * @param string|\ManaPHP\Caching\Store\AdapterInterface $adapter
         * @param \ManaPHP\Caching\Serializer\AdapterInterface   $serializer
         *
         * @throws \ManaPHP\Di\Exception
         */
        public function __construct($prefix = '', $adapter = null, $serializer = null)
        {
            $this->_prefix = $prefix;

            if ($adapter === null) {
                $adapter = 'defaultStoreCache';
            }

            $this->_adapter = is_string($adapter) ? Di::getDefault()->getShared($adapter) : $adapter;
            $this->_serializer = $serializer ?: new JsonPhp();
        }

        /**
         * Fetch content
         *
         * @param string $id
         *
         * @return mixed
         * @throws \ManaPHP\Caching\Store\Exception
         */
        public function get($id)
        {
            $content = $this->_adapter->get($this->_prefix . $id);
            if ($content === false) {
                return false;
            }

            return $this->_serializer->deserialize($content);
        }

        /**
         * Retrieves a value from store with a specified id.
         *
         * @param array $ids
         *
         * @return array
         * @throws \ManaPHP\Caching\Store\Exception
         */
        public function mGet($ids)
        {
            $completeIds = [];
            foreach ($ids as $id) {
                $completeIds[] = $this->_prefix . $id;
            }

            $completeIdValues = $this->_adapter->mGet($completeIds);
            $idValues = [];
            foreach ($completeIdValues as $completeId => $value) {
                $id = substr($completeId, strlen($this->_prefix));
                if ($value === false) {
                    $idValues[$id] = $value;
                } else {
                    $idValues[$id] = $this->_serializer->deserialize($value);
                }
            }

            return $idValues;
        }

        /**
         * Stores content
         *
         * @param string $id
         * @param mixed  $value
         *
         * @return void
         * @throws \ManaPHP\Caching\Cache\Exception
         */
        public function set($id, $value)
        {
            $this->_adapter->set($this->_prefix . $id, $this->_serializer->serialize($value));
        }

        /**
         * Stores a value identified by a id into store.
         *
         * @param array $idValues
         *
         * @return void
         */
        public function mSet($idValues)
        {
            $completeIdValues = [];
            foreach ($idValues as $id => $value) {
                $completeIdValues[$this->_prefix . $id] = $this->_serializer->serialize($value);
            }

            $this->_adapter->mSet($completeIdValues);
        }

        /**
         * Delete content
         *
         * @param string $id
         *
         * @void
         */
        public function delete($id)
        {
            $this->_adapter->delete($this->_prefix . $id);
        }

        /**
         * Check if id exists
         *
         * @param string $id
         *
         * @return bool
         */
        public function exists($id)
        {
            return $this->_adapter->exists($this->_prefix . $id);
        }

        /**
         * @return \ManaPHP\Caching\Store\AdapterInterface
         */
        public function getAdapter()
        {
            return $this->_adapter;
        }

        /**
         * @return \ManaPHP\Caching\Serializer\AdapterInterface
         */
        public function getSerializer()
        {
            return $this->_serializer;
        }

        /**
         * @return array
         */
        public function __debugInfo()
        {
            return get_object_vars($this) ?: [];
        }
    }
}