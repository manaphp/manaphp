<?php
namespace ManaPHP\Authentication {

    interface   UserIdentifierInterface
    {
        /**
         * @return int|string
         */
        public function getId();

        /**
         * @return string
         */
        public function getName();
    }
}