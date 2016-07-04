<?php
namespace ManaPHP\Authentication {

    interface   UserIdentityInterface
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