<?php
namespace ManaPHP\Mvc {

    interface WidgetInterface
    {
        /**
         * @param $vars
         *
         * @return string|array
         */
        public function run($vars);
    }
}