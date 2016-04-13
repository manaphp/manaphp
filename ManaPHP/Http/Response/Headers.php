<?php

namespace ManaPHP\Http\Response {

    /**
     * ManaPHP\Http\Response\Headers
     *
     * This class is a bag to manage the response headers
     */
    class Headers implements HeadersInterface
    {

        protected $_headers = [];

        /**
         * Sets a header to be sent at the end of the request
         *
         * @param string $name
         * @param string $value
         */
        public function set($name, $value)
        {
            $this->_headers[$name] = $value;
        }

        /**
         * Sets a raw header to be sent at the end of the request
         *
         * @param string $header
         */
        public function setRaw($header)
        {
            $this->_headers[$header] = null;
        }

        /**
         * Removes a header to be sent at the end of the request
         *
         * @param string $header_index
         */
        public function remove($header_index)
        {
            unset($this->_headers[$header_index]);
        }

        /**
         * Sends the headers to the client
         *
         * @return boolean
         */
        public function send()
        {
            if (!headers_sent()) {
                if (isset($this->_headers['Status'])) {
                    header('HTTP/1.1 ' . $this->_headers['Status']);
                }

                foreach ($this->_headers as $header => $value) {
                    if ($value !== null) {
                        header($header . ': ' . $value, true);
                    } else {
                        header($header, true);
                    }
                }

                return true;
            } else {
                return false;
            }
        }

        /**
         * Returns the current headers as an array
         *
         * @return array
         */
        public function toArray()
        {
            return $this->_headers;
        }
    }
}
