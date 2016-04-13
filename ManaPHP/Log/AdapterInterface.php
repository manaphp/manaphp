<?php
namespace ManaPHP\Log {

    interface AdapterInterface
    {

        /**
         * @param string $level
         * @param string $message
         * @param array  $context
         *
         * @return void
         */
        public function log($level, $message, $context = null);
    }
}