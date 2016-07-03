<?php
namespace ManaPHP\Mvc\Model\MetaData {

    interface AdapterInterface
    {
        /**
         * Reads the meta-data from temporal memory
         *
         * @param string $key
         *
         * @return array|false
         */
        public function read($key);

        /**
         * Writes the meta-data to temporal memory
         *
         * @param string $key
         * @param array  $data
         */
        public function write($key, $data);
    }
}