<?php

namespace ManaPHP\Mvc\Router {

    /**
     * ManaPHP\Mvc\Router\RouteInterface initializer
     */
    interface RouteInterface
    {
        /**
         * Returns the paths
         *
         * @return array
         */
        public function getPaths();

        /**
         * @param string $handle_uri
         * @param array  $matches
         *
         * @return bool
         */
        public function isMatched($handle_uri, &$matches);
    }
}
