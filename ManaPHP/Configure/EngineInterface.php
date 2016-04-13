<?php
namespace ManaPHP\Configure {

    interface EngineInterface
    {
        /**
         * @param string $file
         *
         * @return mixed
         */
        public function load($file);
    }
}