<?php
namespace ManaPHP {

    interface ApplicationInterface
    {
        /**
         * @return string
         */
        public function getAppDir();

        public function getAppNamespace();

        public function getDataDir();
    }
}