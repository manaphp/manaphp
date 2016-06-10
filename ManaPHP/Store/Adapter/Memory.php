<?php
namespace ManaPHP\Store\Adapter {

    use ManaPHP\Store\AdapterInterface;

    class Memory implements AdapterInterface
    {
        /**
         * @var array
         */
        protected $_data = [];

        public function get($id)
        {
            return isset($this->_data[$id]) ? $this->_data[$id] : false;
        }

        public function mGet($ids)
        {
            $idValues = [];
            foreach ($ids as $id) {
                $idValues[$id] = isset($this->_data[$id]) ? $this->_data[$id] : false;
            }

            return $idValues;
        }

        public function set($id, $value)
        {
            $this->_data[$id] = $value;
        }

        public function mSet($idValues)
        {
            foreach ($idValues as $id => $value) {
                $this->_data[$id] = $value;
            }
        }

        public function delete($id)
        {
            unset($this->_data[$id]);
        }

        public function exists($id)
        {
            return isset($this->_data[$id]);
        }
    }
}