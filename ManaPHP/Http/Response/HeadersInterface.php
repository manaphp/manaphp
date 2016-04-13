<?php

namespace ManaPHP\Http\Response {

    /**
     * ManaPHP\Http\Response\HeadersInterface initializer
     */
    interface HeadersInterface
    {

        /**
         * Sets a header to be sent at the end of the request
         *
         * @param string $name
         * @param string $value
         */
        public function set($name, $value);

        /**
         * Sets a raw header to be sent at the end of the request
         *
         * @param string $header
         */
        public function setRaw($header);

        /**
         * Removes a header to be sent at the end of the request
         *
         * @param string $header_index
         */
        public function remove($header_index);

        /**
         * Sends the headers to the client
         *
         * @return boolean
         */
        public function send();

        /**
         * Returns the current headers as an array
         *
         * @return array
         */
        public function toArray();

    }
}
