<?php
namespace ManaPHP\Mvc {

    interface UrlInterface
    {
        /**
         * Sets a prefix to all the urls generated
         *
         * @param string $prefix
         *
         * @return static
         */
        public function setPrefix($prefix);

        /**
         * Returns the prefix for all the generated urls.
         */
        public function getPrefix();

        /**
         * @param string $uri
         * @param array  $args
         *
         * @return mixed
         */
        public function get($uri = null, $args = null);

        /**
         * @param string      $uri
         * @param bool|string $correspondingMin
         *
         * @return string
         */
        public function getCss($uri, $correspondingMin = true);

        /**
         * @param string      $uri
         * @param bool|string $correspondingMin
         *
         * @return string
         */
        public function getJs($uri, $correspondingMin = true);
    }
}