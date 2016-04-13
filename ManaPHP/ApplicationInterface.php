<?php
namespace ManaPHP {

    interface ApplicationInterface
    {
        /**
         * @return string
         */
        public function getAppPath();

        public function getAppNamespace();

        public function getDataPath();
    }
}