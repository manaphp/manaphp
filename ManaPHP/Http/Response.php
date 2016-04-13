<?php

namespace ManaPHP\Http {

    use ManaPHP\Component;
    use ManaPHP\Di;
    use ManaPHP\Http\Response\Exception;
    use ManaPHP\Http\Response\Headers;

    /**
     * ManaPHP\Http\Response
     *
     * Part of the HTTP cycle is return responses to the clients.
     * ManaPHP\HTTP\Response is the ManaPHP component responsible to achieve this task.
     * HTTP responses are usually composed by headers and body.
     *
     *<code>
     *    $response = new ManaPHP\Http\Response();
     *    $response->setStatusCode(200, "OK");
     *    $response->setContent("<html><body>Hello</body></html>");
     *    $response->send();
     *</code>
     */
    class Response extends Component implements ResponseInterface
    {
        /**
         * @var boolean
         */
        protected $_sent = false;

        /**
         * @var string
         */
        protected $_content = null;

        /**
         * @var \ManaPHP\Http\Response\HeadersInterface
         */
        protected $_headers;

        /**
         * @var \ManaPHP\Http\Response\CookiesInterface
         */
        protected $_cookies;

        protected $_file;

        public function __construct()
        {
            $this->_headers = new Headers();
        }

        /**
         * Sets the HTTP response code
         *
         *<code>
         *    $response->setStatusCode(404, "Not Found");
         *</code>
         *
         * @param int    $code
         * @param string $message
         *
         * @return static
         * @throws
         */
        public function setStatusCode($code, $message)
        {
            $this->setHeader('Status', $code . ' ' . $message);

            return $this;
        }

        /**
         * Sets a cookies bag for the response externally
         *
         * @param \ManaPHP\Http\Response\CookiesInterface $cookies
         *
         * @return static
         */
        public function setCookies($cookies)
        {
            $this->_cookies = $cookies;

            return $this;
        }

        /**
         * Returns cookies set by the user
         *
         * @return \ManaPHP\Http\Response\CookiesInterface
         */
        public function getCookies()
        {
            return $this->_cookies;
        }

        /**
         * Overwrites a header in the response
         *
         *<code>
         *    $response->setHeader("Content-Type", "text/plain");
         *</code>
         *
         * @param string $name
         * @param string $value
         *
         * @return static
         */
        public function setHeader($name, $value)
        {
            $this->_headers->set($name, $value);

            return $this;
        }

        /**
         * Send a raw header to the response
         *
         *<code>
         *    $response->setRawHeader("HTTP/1.1 404 Not Found");
         *</code>
         *
         * @param string $header
         *
         * @return static
         */
        public function setRawHeader($header)
        {
            $this->_headers->setRaw($header);

            return $this;
        }

        /**
         * Sets a Expires header to use HTTP cache
         *
         *<code>
         *    $this->response->setExpires(new DateTime());
         *</code>
         *
         * @param int|\DateTime $datetime
         *
         * @return static
         */
        public function setExpires($datetime)
        {
            if (is_int($datetime)) {
                $date = new \DateTime('now', new \DateTimeZone('UTC'));
                $date->setTimestamp($datetime);
            } else {
                $date = clone $datetime;
            }

            $date->setTimezone(new \DateTimeZone('UTC'));
            $this->setHeader('Expires', $date->format('D, d M Y H:i:s') . ' GMT');

            return $this;
        }

        /**
         * Sets a Not-Modified response
         *
         * @return static
         */
        public function setNotModified()
        {
            $this->setStatusCode(304, 'Not modified');

            return $this;
        }

        /**
         * Sets the response content-type mime, optionally the charset
         *
         *<code>
         *    $response->setContentType('application/pdf');
         *    $response->setContentType('text/plain', 'UTF-8');
         *</code>
         *
         * @param string $contentType
         * @param string $charset
         *
         * @return static
         */
        public function setContentType($contentType, $charset = null)
        {
            if ($charset === null) {
                $this->_headers->set('Content-Type', $contentType);
            } else {
                $this->_headers->set('Content-Type', $contentType . '; charset=' . $charset);
            }

            return $this;
        }

        /**
         * Set a custom ETag
         *
         *<code>
         *    $response->setEtag(md5(time()));
         *</code>
         *
         * @param string $etag
         *
         * @return static
         */
        public function setEtag($etag)
        {
            $this->_headers->set('Etag', $etag);

            return $this;
        }

        /**
         * Redirect by HTTP to another action or URL
         *
         *<code>
         *  //Using a string redirect (internal/external)
         *    $response->redirect("posts/index");
         *    $response->redirect("http://www.google.com", true);
         *    $response->redirect("http://www.example.com/new-location", true, 301);
         *
         *    //Making a redirection based on a named route
         *    $response->redirect(array(
         *        "for" => "index-lang",
         *        "lang" => "jp",
         *        "controller" => "index"
         *    ));
         *</code>
         *
         * @param string|array $location
         * @param boolean      $externalRedirect
         * @param int|string   $statusCode
         *
         * @return static
         * @throws \ManaPHP\Http\Response\Exception
         */
        public function redirect($location, $externalRedirect = false, $statusCode = 302)
        {
            if (is_string($statusCode)) {
                $statusCode = (int)$statusCode;
            }

            /**
             * The HTTP status is 302 by default, a temporary redirection
             */
            if ($statusCode === 301) {
                $message = 'Permanently Moved';
            } elseif ($statusCode === 302) {
                $message = 'Temporarily Moved';
            } else {
                throw new Exception('invalid status code: ' . $statusCode);
            }

            $this->setStatusCode($statusCode, $message);

            /**
             * Change the current location using 'Location'
             */
            $this->setHeader('Location', $location);

            return $this;
        }

        /**
         * Sets HTTP response body
         *
         *<code>
         *    $response->setContent("<h1>Hello!</h1>");
         *</code>
         *
         * @param string $content
         *
         * @return static
         */
        public function setContent($content)
        {
            $this->_content = $content;

            return $this;
        }

        /**
         * Sets HTTP response body. The parameter is automatically converted to JSON
         *
         *<code>
         *    $response->setJsonContent(array("status" => "OK"));
         *    $response->setJsonContent(array("status" => "OK"), JSON_NUMERIC_CHECK);
         *</code>
         *
         * @param string $content
         * @param int    $jsonOptions consisting on http://www.php.net/manual/en/json.constants.php
         *
         * @return static
         */
        public function setJsonContent($content, $jsonOptions = null)
        {
            if ($jsonOptions === null) {
                $jsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
            }

            $this->_content = json_encode($content, $jsonOptions, 512);

            return $this;
        }

        /**
         * Appends a string to the HTTP response body
         *
         * @param string $content
         *
         * @return static
         */
        public function appendContent($content)
        {
            $this->_content .= $content;

            return $this;
        }

        /**
         * Gets the HTTP response body
         *
         * @return string
         */
        public function getContent()
        {
            return $this->_content;
        }

        /**
         * Check if the response is already sent
         *
         * @return boolean
         */
        public function isSent()
        {
            return $this->_sent;
        }

        /**
         * Sends headers to the client
         *
         * @return static
         */
        public function sendHeaders()
        {
            if (is_object($this->_headers)) {
                $this->_headers->send();
            }

            return $this;
        }

        /**
         * Sends cookies to the client
         *
         * @return static
         */
        public function sendCookies()
        {
            if (is_object($this->_cookies)) {
                $this->_cookies->send();
            }

            return $this;
        }

        /**
         * Prints out HTTP response to the client
         *
         * @return static
         * @throws \ManaPHP\Http\Response\Exception
         */
        public function send()
        {
            if ($this->_sent === true) {
                throw new Exception('Response was already sent');
            }

            if (is_object($this->_headers)) {
                $this->_headers->send();
            }

            if (is_object($this->_cookies)) {
                $this->_cookies->send();
            }

            if ($this->_content !== null) {
                echo $this->_content;
            } else {
                if (is_string($this->_file) && $this->_file !== '') {
                    readfile($this->_file);
                }
            }

            $this->_sent = true;

            return $this;
        }

        /**
         * Sets an attached file to be sent at the end of the request
         *
         * @param string $filePath
         * @param string $attachmentName
         *
         * @return static
         */
        public function setFileToSend($filePath, $attachmentName = null)
        {
            if ($attachmentName === null) {
                $attachmentName = basename($filePath);
            }

            $this->_headers->setRaw('Content-Description: File Transfer');
            $this->_headers->setRaw('Content-Type: application/octet-stream');
            $this->_headers->setRaw('Content-Disposition: attachment; filename=' . $attachmentName);
            $this->_headers->setRaw('Content-Transfer-Encoding: binary');
            $this->_file = $filePath;

            return $this;
        }
    }
}
