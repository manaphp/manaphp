<?php

namespace ManaPHP\Http {

    use ManaPHP\Component;
    use ManaPHP\Http\Request\Exception;
    use ManaPHP\Http\Request\File;
    use ManaPHP\Utility\Text;

    /**
     * ManaPHP\Http\Request
     *
     * <p>Encapsulates request information for easy and secure access from application controllers.</p>
     *
     * <p>The request object is a simple value object that is passed between the dispatcher and controller classes.
     * It packages the HTTP request environment.</p>
     *
     *<code>
     *    $request = new ManaPHP\Http\Request();
     *    if ($request->isPost() == true) {
     *        if ($request->isAjax() == true) {
     *            echo 'Request was made using POST and AJAX';
     *        }
     *    }
     *</code>
     *
     */
    class Request extends Component implements RequestInterface
    {
        protected $_rawBody;

        /**
         * @var array
         */
        protected $_putCache;

        /**
         * @var string
         */
        protected $_client_address;

        /**
         * @var array
         */
        protected $_rules = [];

        /**
         * @param array $rules
         *
         * @return static
         */
        public function setRules($rules)
        {
            $this->_rules = array_merge($this->_rules, $rules);
            return $this;
        }

        /**
         *
         * @param array        $source
         * @param string       $name
         * @param string|array $rules
         * @param mixed        $defaultValue
         *
         * @return string
         * @throws \ManaPHP\Http\Request\Exception
         */
        protected function _getHelper($source, $name = null, $rules = null, $defaultValue = null)
        {
            if ($name === null) {

                $data = [];

                if ($rules === null) {
                    $rules = [];
                }

                if (is_string($rules)) {
                    /** @noinspection SuspiciousLoopInspection */
                    foreach ($source as $name => $_) {
                        $data[$name] = $this->_getHelper($source, $name, $rules);
                    }
                } else {
                    /** @noinspection SuspiciousLoopInspection */
                    foreach ($source as $name => $_) {
                        $data[$name] = $this->_getHelper($source, $name, isset($rules[$name]) ? $rules[$name] : null);
                    }
                }

                return $data;
            }

            $value = isset($source[$name]) ? $source[$name] : $defaultValue;

            if ($rules === null) {
                if ($value === null) {
                    return null;
                } else {
                    $rules = isset($this->_rules[$name]) ? $this->_rules[$name] : '';
                }
            }

            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return $this->filter->sanitize($name, $rules, $value);
        }

        /**
         * Gets a variable from the $_REQUEST applying filters if needed.
         * If no parameters are given the $_REQUEST is returned
         *
         *<code>
         *    //Returns value from $_REQUEST["user_email"] without sanitizing
         *    $userEmail = $request->get("user_email");
         *
         *    //Returns value from $_REQUEST["user_email"] with sanitizing
         *    $userEmail = $request->get("user_email", "email");
         *</code>
         *
         * @param string $name
         * @param string $rules
         * @param mixed  $defaultValue
         *
         * @return mixed
         * @throws \ManaPHP\Http\Request\Exception
         */
        public function get($name = null, $rules = null, $defaultValue = null)
        {
            return $this->_getHelper($_REQUEST, $name, $rules, $defaultValue);
        }

        /**
         * Gets variable from $_GET applying filters if needed
         * If no parameters are given the $_GET is returned
         *
         *<code>
         *    //Returns value from $_GET["id"] without sanitizing
         *    $id = $request->getGet("id");
         *
         *    //Returns value from $_GET["id"] with sanitizing
         *    $id = $request->getGet("id", "int");
         *
         *    //Returns value from $_GET["id"] with a default value
         *    $id = $request->getGet("id", null, 150);
         *</code>
         *
         * @param string $name
         * @param string $rules
         * @param mixed  $defaultValue
         *
         * @return mixed
         * @throws \ManaPHP\Http\Request\Exception
         */
        public function getGet($name = null, $rules = null, $defaultValue = null)
        {
            return $this->_getHelper($_GET, $name, $rules, $defaultValue);
        }

        /**
         * Gets a variable from the $_POST applying filters if needed
         * If no parameters are given the $_POST is returned
         *
         *<code>
         *    //Returns value from $_POST["user_email"] without sanitizing
         *    $userEmail = $request->getPost("user_email");
         *
         *    //Returns value from $_POST["user_email"] with sanitizing
         *    $userEmail = $request->getPost("user_email", "email");
         *</code>
         *
         * @param string $name
         * @param string $rules
         * @param mixed  $defaultValue
         *
         * @return mixed
         * @throws \ManaPHP\Http\Request\Exception
         */
        public function getPost($name = null, $rules = null, $defaultValue = null)
        {
            return $this->_getHelper($_POST, $name, $rules, $defaultValue);
        }

        /**
         * Gets variable from $_SERVER applying filters if needed
         *
         * @param string       $name
         * @param string|array $rules
         * @param mixed        $defaultValue
         *
         * @return mixed
         * @throws \ManaPHP\Http\Request\Exception
         */
        public function getServer($name = null, $rules = null, $defaultValue = null)
        {
            return $this->_getHelper($_SERVER, $name, $rules, $defaultValue);
        }

        /**
         * Gets a variable from put request
         *
         *<code>
         *    $userEmail = $request->getPut("user_email");
         *
         *    $userEmail = $request->getPut("user_email", "email");
         *</code>
         *
         * @param string       $name
         * @param string|array $rules
         * @param mixed        $defaultValue
         *
         * @return mixed
         * @throws \ManaPHP\Http\Request\Exception
         */
        public function getPut($name = null, $rules = null, $defaultValue = null)
        {
            if ($this->_putCache === null && $this->isPut()) {
                parse_str($this->getRawBody(), $this->_putCache);
            }

            return $this->_getHelper($this->_putCache, $name, $rules, $defaultValue);
        }

        /**
         * Gets variable from $_GET applying filters if needed
         * If no parameters are given the $_GET is returned
         *
         *<code>
         *    //Returns value from $_GET["id"] without sanitizing
         *    $id = $request->getQuery("id");
         *
         *    //Returns value from $_GET["id"] with sanitizing
         *    $id = $request->getQuery("id", "int");
         *
         *    //Returns value from $_GET["id"] with a default value
         *    $id = $request->getQuery("id", null, 150);
         *</code>
         *
         * @param string       $name
         * @param string|array $rules
         * @param mixed        $defaultValue
         *
         * @return mixed
         * @throws \ManaPHP\Http\Request\Exception
         */
        public function getQuery($name = null, $rules = null, $defaultValue = null)
        {
            return $this->_getHelper($_GET, $name, $rules, $defaultValue);
        }

        /**
         * Checks whether $_REQUEST has certain index
         *
         * @param string $name
         *
         * @return boolean
         */
        public function has($name)
        {
            return isset($_REQUEST[$name]);
        }

        /**
         * Checks whether $_GET has certain index
         *
         * @param string $name
         *
         * @return boolean
         */
        public function hasGet($name)
        {
            return isset($_GET[$name]);
        }

        /**
         * Checks whether $_POST has certain index
         *
         * @param string $name
         *
         * @return boolean
         */
        public function hasPost($name)
        {
            return isset($_POST[$name]);
        }

        /**
         * Checks whether put has certain index
         *
         * @param string $name
         *
         * @return boolean
         */
        public function hasPut($name)
        {
            if ($this->_putCache === null && $this->isPut()) {
                parse_str($this->getRawBody(), $this->_putCache);
            }

            return isset($this->_putCache[$name]);
        }

        /**
         * Checks whether $_GET has certain index
         *
         * @param string $name
         *
         * @return boolean
         */
        public function hasQuery($name)
        {
            return isset($_GET[$name]);
        }

        /**
         * Checks whether $_GET has certain index
         *
         * @param string $name
         *
         * @return boolean
         */
        public function hasServer($name)
        {
            return isset($_SERVER[$name]);
        }

        /**
         * @return string
         */
        public function getMethod()
        {
            return $_SERVER['REQUEST_METHOD'];
        }

        /**
         * Gets HTTP schema (http/https)
         *
         * @return string
         * @throws \ManaPHP\Http\Request\Exception
         */
        public function getScheme()
        {
            if (isset($_SERVER['REQUEST_SCHEME'])) {
                return $_SERVER['REQUEST_SCHEME'];
            } elseif (isset($_SERVER['HTTPS'])) {
                if ($_SERVER['HTTPS'] === 'on') {
                    return 'https';
                } else {
                    return 'http';
                }
            } else {
                throw new Exception('HTTPS field not exists in $_SERVER');
            }
        }

        /**
         * Checks whether request has been made using ajax. Checks if $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest'
         *
         * @return boolean
         */
        public function isAjax()
        {
            return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
        }

        /**
         * Gets HTTP raw request body
         *
         * @return string
         */
        public function getRawBody()
        {
            if ($this->_rawBody === null) {
                $this->_rawBody = file_get_contents('php://input');
            }

            return $this->_rawBody;
        }

        /**
         * Gets most possible client IPv4 Address. This method search in $_SERVER['REMOTE_ADDR'] and optionally in $_SERVER['HTTP_X_FORWARDED_FOR']
         *
         * @return string
         */
        public function getClientAddress()
        {
            if ($this->_client_address === null) {
                if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $this->_client_address = $_SERVER['REMOTE_ADDR'];
                } else {
                    $client_address = $_SERVER['REMOTE_ADDR'];
                    if (Text::startsWith($client_address, '127.0.') || Text::startsWith($client_address,
                            '192.168.') || Text::startsWith($client_address, '10.')
                    ) {
                        $this->_client_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
                    } else {
                        $this->_client_address = $_SERVER['REMOTE_ADDR'];
                    }
                }
            }

            return $this->_client_address;
        }

        /**set the client address for getClientAddress method
         *
         * @param string|callable
         */
        public function setClientAddress($address)
        {
            if (is_string($address)) {
                $this->_client_address = $address;
            } else {
                $this->_client_address = $address();
            }
        }

        /**
         * Gets HTTP user agent used to made the request
         *
         * @return string
         */
        public function getUserAgent()
        {
            return strip_tags(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
        }

        /**
         * Checks whether HTTP method is POST. if $_SERVER['REQUEST_METHOD']=='POST'
         *
         * @return boolean
         */
        public function isPost()
        {
            return $_SERVER['REQUEST_METHOD'] === 'POST';
        }

        /**
         * Checks whether HTTP method is GET. if $_SERVER['REQUEST_METHOD']=='GET'
         *
         * @return boolean
         */
        public function isGet()
        {
            return $_SERVER['REQUEST_METHOD'] === 'GET';
        }

        /**
         * Checks whether HTTP method is PUT. if $_SERVER['REQUEST_METHOD']=='PUT'
         *
         * @return boolean
         */
        public function isPut()
        {
            return $_SERVER['REQUEST_METHOD'] === 'PUT';
        }

        /**
         * Checks whether HTTP method is PATCH. if $_SERVER['REQUEST_METHOD']=='PATCH'
         *
         * @return boolean
         */
        public function isPatch()
        {
            return $_SERVER['REQUEST_METHOD'] === 'PATCH';
        }

        /**
         * Checks whether HTTP method is HEAD. if $_SERVER['REQUEST_METHOD']=='HEAD'
         *
         * @return boolean
         */
        public function isHead()
        {
            return $_SERVER['REQUEST_METHOD'] === 'HEAD';
        }

        /**
         * Checks whether HTTP method is DELETE. if $_SERVER['REQUEST_METHOD']=='DELETE'
         *
         * @return boolean
         */
        public function isDelete()
        {
            return $_SERVER['REQUEST_METHOD'] === 'DELETE';
        }

        /**
         * Checks whether HTTP method is OPTIONS. if $_SERVER['REQUEST_METHOD']=='OPTIONS'
         *
         * @return boolean
         */
        public function isOptions()
        {
            return $_SERVER['REQUEST_METHOD'] === 'OPTIONS';
        }

        /**
         * Checks whether request includes attached files
         * http://php.net/manual/en/features.file-upload.multiple.php
         *
         * @param boolean $onlySuccessful
         *
         * @return boolean
         */
        public function hasFiles($onlySuccessful = false)
        {
            foreach ($_FILES as $file) {
                if (is_int($file['error'])) {
                    $error = $file['error'];

                    if (!$onlySuccessful || $error === UPLOAD_ERR_OK) {
                        return true;
                    }
                } else {
                    /** @noinspection PhpWrongForeachArgumentTypeInspection */
                    foreach ($file['error'] as $error) {
                        if (!$onlySuccessful || $error === UPLOAD_ERR_OK) {
                            return true;
                        }
                    }
                }
            }

            return false;
        }

        /**
         * Gets attached files as \ManaPHP\Http\Request\File instances
         *
         * @param boolean $onlySuccessful
         *
         * @return \ManaPHP\Http\Request\File[]
         */
        public function getFiles($onlySuccessful = false)
        {
            $files = [];

            foreach ($_FILES as $key => $file) {
                if (is_int($file['error'])) {
                    if (!$onlySuccessful || $file['error'] === UPLOAD_ERR_OK) {
                        $files[] = new File($key, [
                            'name' => $file['name'],
                            'type' => $file['type'],
                            'tmp_name' => $file['tmp_name'],
                            'error' => $file['error'],
                            'size' => $file['size'],
                        ]);
                    }
                } else {
                    $countFiles = count($file['error']);
                    /** @noinspection ForeachInvariantsInspection */
                    for ($i = 0; $i < $countFiles; $i++) {
                        if (!$onlySuccessful || $file['error'][$i] === UPLOAD_ERR_OK) {
                            $files[] = new File($key, [
                                'name' => $file['name'][$i],
                                'type' => $file['type'][$i],
                                'tmp_name' => $file['tmp_name'][$i],
                                'error' => $file['error'][$i],
                                'size' => $file['size'][$i],
                            ]);
                        }
                    }
                }
            }

            return $files;
        }

        /**
         * Gets web page that refers active request. ie: http://www.google.com
         *
         * @return string
         */
        public function getReferer()
        {
            return strip_tags(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
        }

        /**
         * @param bool $withQuery
         *
         * @return string
         */
        public function getUrl($withQuery = false)
        {
            $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

            if ($withQuery) {
                $get = $_GET;
                unset($get['_url']);

                $query = http_build_query($get);
                if ($query) {
                    $url .= '?' . $query;
                }
            }

            return strip_tags($url);
        }

        /**
         * @return string
         */
        public function getUri()
        {
            return strip_tags($_SERVER['REQUEST_URI']);
        }
    }
}
