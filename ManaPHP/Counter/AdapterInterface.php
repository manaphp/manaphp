<?php
namespace ManaPHP\Counter {

    interface AdapterInterface
    {
        /**
         * @param string|array $key
         * @return int
         */
        public function _get($key);

        /**
         * @param string|array $key
         * @param int $step
         *
         * @return int
         */
        public function _increment($key, $step);

        /**
         * @param string|array $key
         *
         * @return void
         */
        public function _delete($key);
    }
}