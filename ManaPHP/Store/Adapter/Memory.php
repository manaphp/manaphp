<?php
namespace ManaPHP\Store\Adapter {

    use ManaPHP\Store;

    class Memory extends Store
    {
        /**
         * @var array
         */
        protected $_data = [];

        public function _get($id)
        {
            return isset($this->_data[$id]) ? $this->_data[$id] : false;
        }

        public function _mGet($ids)
        {
            $idValues = [];
            foreach ($ids as $id) {
                $idValues[$id] = isset($this->_data[$id]) ? $this->_data[$id] : false;
            }

            return $idValues;
        }

        public function _set($id, $value)
        {
            $this->_data[$id] = $value;
        }

        public function _mSet($idValues)
        {
            foreach ($idValues as $id => $value) {
                $this->_data[$id] = $value;
            }
        }

        public function _delete($id)
        {
            unset($this->_data[$id]);
        }

        public function _exists($id)
        {
            return isset($this->_data[$id]);
        }
    }
}