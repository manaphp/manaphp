<?php
namespace ManaPHP\Http {

    interface ClientInterface
    {
        /**
         * @param string|array $url
         * @param array        $headers
         * @param array        $options
         *
         * @return int
         */
        public function get($url, $headers = [], $options = []);

        /**
         * @param string|array $url
         * @param array        $data
         * @param array        $headers
         * @param array        $options
         *
         * @return int
         */
        public function post($url, $data = [], $headers = [], $options = []);

        /**
         * @param string|array $url
         * @param array        $headers
         * @param array        $options
         *
         * @return int
         */
        public function delete($url, $headers = [], $options = []);

        /**
         * @param string|array $url
         * @param array        $data
         * @param array        $headers
         * @param array        $options
         *
         * @return int
         */
        public function put($url, $data = [], $headers = [], $options = []);

        /**
         * @param string|array $url
         * @param array        $data
         * @param array        $headers
         * @param array        $options
         *
         * @return int
         */
        public function patch($url, $data = [], $headers = [], $options = []);

        /**
         * @return string
         */
        public function getResponseBody();
    }
}