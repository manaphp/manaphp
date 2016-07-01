<?php
namespace ManaPHP\Security\Token {

    interface AdapterInterface
    {
        /**
         * @param array $data
         *
         * @return string
         */
        public function _encode($data);

        /**
         * @param string $data
         *
         * @return array
         */
        public function _decode($data);
    }
}