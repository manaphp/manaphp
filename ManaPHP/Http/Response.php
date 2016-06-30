<?php

namespace ManaPHP\Http {

    use ManaPHP\Component;
    use ManaPHP\Di;
    use ManaPHP\Http\Response\Exception;
    use ManaPHP\Utility\Text;

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
        protected $_content;

        /**
         * @var array
         */
        protected $_headers = [];

        /**
         * @var string
         */
        protected $_file;

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
            $this->_headers[$name] = $value;

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
            $this->_headers[$header] = null;

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
                $this->setHeader('Content-Type', $contentType);
            } else {
                $this->setHeader('Content-Type', $contentType . '; charset=' . $charset);
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
            $this->setHeader('Etag', $etag);

            return $this;
        }

        /**
         * Redirect by HTTP to another action or URL
         *
         *<code>
         *  //Using a string redirect (internal/external)
         *    $response->redirect("posts/index");
         *    $response->redirect("http://www.google.com");
         *    $response->redirect("http://www.example.com/new-location", 301);
         *</code>
         *
         * @param string|array $location
         * @param int|string   $statusCode
         *
         * @return static
         * @throws \ManaPHP\Http\Response\Exception
         */
        public function redirect($location, $statusCode = 302)
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
         * @param mixed $content
         * @param int   $jsonOptions consisting on http://www.php.net/manual/en/json.constants.php
         *
         * @return static
         */
        public function setJsonContent($content, $jsonOptions = null)
        {
            $this->setContentType('application/json', 'utf-8');

            if ($jsonOptions === null) {
                $jsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
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
         * @throws \ManaPHP\Http\Cookies\Exception
         */
        public function sendHeaders()
        {
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

            $this->cookies->send();

            return $this;
        }

        /**
         * Prints out HTTP response to the client
         *
         * @return static
         * @throws \ManaPHP\Http\Response\Exception|\ManaPHP\Http\Cookies\Exception
         */
        public function send()
        {
            if ($this->_sent === true) {
                throw new Exception('Response was already sent');
            }

            if (!headers_sent()) {
                $this->sendHeaders();
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
         * @param string $file
         * @param string $attachmentName
         *
         * @return static
         * @throws \ManaPHP\Http\Response\Exception
         */
        public function setFileToSend($file, $attachmentName = null)
        {
            if ($attachmentName === null) {
                $attachmentName = basename($file);
            }

            if (!file_exists($file)) {
                throw new Exception('Sent file is not exists: ' . $file);
            }

            $this->_file = $file;

            $this->setHeader('Content-Length', filesize($file));
            $this->setAttachment($attachmentName);

            return $this;
        }

        public function setAttachment($attachmentName)
        {

            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
                if (Text::contains($userAgent, 'Trident') || Text::contains($userAgent, 'MSIE')) {
                    $attachmentName = urlencode($attachmentName);
                }
            }

            $this->setHeader('Content-Description', 'File Transfer');
            $this->setHeader('Content-Type', 'application/octet-stream');
            $this->setHeader('Content-Disposition', 'attachment; filename=' . $attachmentName);
            $this->setHeader('Content-Transfer-Encoding', 'binary');
            $this->setHeader('Cache-Control', 'must-revalidate');

            return $this;
        }

        /**
         * @param array        $rows
         * @param string       $attachmentName
         * @param array|string $header
         *
         * @return static
         */
        public function setCsvContent($rows, $attachmentName, $header = null)
        {
            if (is_string($header)) {
                $header = explode(',', $header);
            }

            if (pathinfo($attachmentName, PATHINFO_EXTENSION) !== 'csv') {
                $attachmentName .= '.csv';
            }

            $this->setAttachment($attachmentName);

            $file = fopen('php://temp', 'r+');
			
            fprintf($file, "\xEF\xBB\xBF");
			
            if ($header !== null) {
                if (Text::startsWith($header[0], 'ID')) {
                    $header[0] = strtolower($header[0]);
                }

                fputcsv($file, $header);
            }

            foreach ($rows as $row) {
                if (is_object($row)) {
                    if (method_exists($row, 'toArray')) {
                        $data = $row->toArray();
                    } else {
                        $data = (array)$row;
                    }
                } elseif (!is_array($row)) {
                    $data = [$row];
                } else {
                    $data = $row;
                }

                fputcsv($file, $data);
            }

            rewind($file);
            $content = stream_get_contents($file);
            fclose($file);

            $this->setContentType('text/csv');
            $this->setHeader('Content-Length', strlen($content));
            $this->setContent($content);

            return $this;
        }

        public function getHeaders()
        {
            return $this->_headers;
        }
    }
}
