<?php
namespace ManaPHP\Authentication {

    interface   UserIdentifierInterface
    {
        /**
         * @return string|int
         */
        public function getId();

        /**
         * @return string
         */
        public function getName();
    }
}