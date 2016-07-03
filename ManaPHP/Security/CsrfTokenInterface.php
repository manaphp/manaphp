<?php
namespace ManaPHP\Security {

    interface CsrfTokenInterface
    {
        /**
         * @return static
         */
        public function disable();

        /**
         * @return string
         */
        public function get();

        /**
         */
        public function verify();
    }
}