<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Request\Exception as RequestException;
use ManaPHP\Http\Request\File;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Http\Request
 *
 * @package request
 *
 * @property \ManaPHP\Http\FilterInterface $filter
 */
class Request extends Component implements RequestInterface
{
    /**
     * @var array
     */
    protected $_put;

    /**
     * @var string
     */
    protected $_clientAddress;

    /**
     * @var array
     */
    protected $_headers;

    /**
     * @var array
     */
    protected $_json;

    /**
     *
     * @param array  $source
     * @param string $name
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return array|string|null
     * @throws \ManaPHP\Http\Request\Exception
     */
    protected function _getHelper($source, $name = null, $rule = null, $defaultValue = '')
    {
        if ($name === null) {
            if ($rule === false || $rule === 'ignore') {
                return $source;
            }

            $data = [];
            foreach ($source as $k => $v) {
                $data[$k] = is_array($v) ? $this->_getHelper($v) : $this->filter->sanitize($k, null, $v);
            }

            return $data;
        } else {
            if ($rule === false || $rule === 'ignore') {
                return isset($source[$name]) ? $source[$name] : $defaultValue;
            }

            if (isset($source[$name])) {
                if (is_array($source[$name])) {
                    return $this->_getHelper($source[$name]);
                } else {
                    return $this->filter->sanitize($name, $rule, $source[$name] !== '' ? $source[$name] : $defaultValue);
                }
            } else {
                return $this->filter->sanitize($name, $rule, $defaultValue);
            }
        }
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
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return mixed
     * @throws \ManaPHP\Http\Request\Exception
     */
    public function get($name = null, $rule = null, $defaultValue = '')
    {
        return $this->_getHelper($_REQUEST, $name, $rule, $defaultValue);
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
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return mixed
     * @throws \ManaPHP\Http\Request\Exception
     */
    public function getGet($name = null, $rule = null, $defaultValue = '')
    {
        return $this->_getHelper($_GET, $name, $rule, $defaultValue);
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
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return mixed
     * @throws \ManaPHP\Http\Request\Exception
     */
    public function getPost($name = null, $rule = null, $defaultValue = '')
    {
        return $this->_getHelper($_POST, $name, $rule, $defaultValue);
    }

    /**
     * Gets variable from $_SERVER
     *
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     * @throws \ManaPHP\Http\Request\Exception
     */
    public function getServer($name = null, $defaultValue = '')
    {
        if ($name === null) {
            return $_SERVER;
        } else {
            return isset($_SERVER[$name]) ? $_SERVER[$name] : $defaultValue;
        }
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
     * @param string $name
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return mixed
     * @throws \ManaPHP\Http\Request\Exception
     */
    public function getPut($name = null, $rule = null, $defaultValue = '')
    {
        if ($this->_put === null && $this->isPut()) {
            parse_str($this->getRawBody(), $this->_put);
        }

        return $this->_getHelper($this->_put, $name, $rule, $defaultValue);
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
     * @param string $name
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return mixed
     * @throws \ManaPHP\Http\Request\Exception
     */
    public function getQuery($name = null, $rule = null, $defaultValue = '')
    {
        return $this->_getHelper($_GET, $name, $rule, $defaultValue);
    }

    /**
     * @throws RequestException
     */
    protected function _initJson()
    {
        global $_JSON;

        if (isset($_JSON)) {
            $this->_json = $_JSON;
        } elseif (isset($_SERVER['HTTP_CONTENT_TYPE']) && strpos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false) {
            $r = json_decode(file_get_contents('php://input'), true);

            if ($r === null) {
                throw new RequestException('json_decode raw body failed.');
            }
            $this->_json = $r;
        } else {
            $this->_json = [];
        }
    }

    /* @param string $name
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return mixed
     * @throws \ManaPHP\Http\Request\Exception
     */
    public function getJson($name = null, $rule = null, $defaultValue = '')
    {
        if ($this->_json === null) {
            $this->_initJson();
        }

        return $this->_getHelper($this->_json, $name, $rule, $defaultValue);
    }

    /**
     * Checks whether $_REQUEST has certain index
     *
     * @param string $name
     *
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
     */
    public function hasPut($name)
    {
        if ($this->_put === null && $this->isPut()) {
            parse_str($this->getRawBody(), $this->_put);
        }

        return isset($this->_put[$name]);
    }

    /**
     * Checks whether $_GET has certain index
     *
     * @param string $name
     *
     * @return bool
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
     * @return bool
     */
    public function hasServer($name)
    {
        return isset($_SERVER[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     * @throws \ManaPHP\Http\Request\Exception
     */
    public function hasJson($name)
    {
        if ($this->_json === null) {
            $this->_initJson();
        }

        return isset($this->_json[$name]);
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return void
     */
    protected function _initHeaders()
    {
        if (function_exists('apache_request_headers')) {
            $this->_headers = array_change_key_case(apache_request_headers(), CASE_UPPER);
        } else {
            /** @noinspection ForeachSourceInspection */
            foreach ($_SERVER as $k => $v) {
                if (strpos($k, 'HTTP_') === 0) {
                    $this->_headers[strtr(substr($k, 5), '_', '-')] = $v;
                }
            }
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader($name)
    {
        if ($this->_headers === null) {
            $this->_initHeaders();
        }

        if (isset($this->_headers[$name])) {
            return true;
        }

        return isset($this->_headers[strtoupper($name)]);
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return array|string|null
     */
    public function getHeader($name = null, $default = null)
    {
        if ($this->_headers === null) {
            $this->_initHeaders();
        }

        if ($name === null) {
            return $this->_headers;
        } else {
            if (isset($this->_headers[$name])) {
                return $this->_headers[$name];
            }

            $ucName = strtoupper($name);
            return isset($this->_headers[$ucName]) ? $this->_headers[$ucName] : $default;
        }
    }

    /**
     * Gets HTTP schema (http/https)
     *
     * @return string
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
            return 'http';
        }
    }

    /**
     * Checks whether request has been made using ajax. Checks if $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest'
     *
     * @return bool
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
        return file_get_contents('php://input');
    }

    /**
     * Gets most possible client IPv4 Address. This method search in $_SERVER['REMOTE_ADDR'] and optionally in $_SERVER['HTTP_X_FORWARDED_FOR']
     *
     * @return string
     */
    public function getClientAddress()
    {
        if ($this->_clientAddress === null) {
            if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $this->_clientAddress = $_SERVER['REMOTE_ADDR'];
            } else {
                $client_address = $_SERVER['REMOTE_ADDR'];
                if (Text::startsWith($client_address, '192.168.') || Text::startsWith($client_address, '10.') || Text::startsWith($client_address, '127.')
                ) {
                    $this->_clientAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } else {
                    $this->_clientAddress = $client_address;
                }
            }
        }

        return $this->_clientAddress;
    }

    /**
     * set the client address for getClientAddress method
     *
     * @param string|callable $address
     *
     * @return static
     */
    public function setClientAddress($address)
    {
        if (is_string($address)) {
            $this->_clientAddress = $address;
        } else {
            $this->_clientAddress = $address();
        }

        return $this;
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
     * @return bool
     */
    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Checks whether HTTP method is GET. if $_SERVER['REQUEST_METHOD']=='GET'
     *
     * @return bool
     */
    public function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Checks whether HTTP method is PUT. if $_SERVER['REQUEST_METHOD']=='PUT'
     *
     * @return bool
     */
    public function isPut()
    {
        return $_SERVER['REQUEST_METHOD'] === 'PUT';
    }

    /**
     * Checks whether HTTP method is PATCH. if $_SERVER['REQUEST_METHOD']=='PATCH'
     *
     * @return bool
     */
    public function isPatch()
    {
        return $_SERVER['REQUEST_METHOD'] === 'PATCH';
    }

    /**
     * Checks whether HTTP method is HEAD. if $_SERVER['REQUEST_METHOD']=='HEAD'
     *
     * @return bool
     */
    public function isHead()
    {
        return $_SERVER['REQUEST_METHOD'] === 'HEAD';
    }

    /**
     * Checks whether HTTP method is DELETE. if $_SERVER['REQUEST_METHOD']=='DELETE'
     *
     * @return bool
     */
    public function isDelete()
    {
        return $_SERVER['REQUEST_METHOD'] === 'DELETE';
    }

    /**
     * Checks whether HTTP method is OPTIONS. if $_SERVER['REQUEST_METHOD']=='OPTIONS'
     *
     * @return bool
     */
    public function isOptions()
    {
        return $_SERVER['REQUEST_METHOD'] === 'OPTIONS';
    }

    /**
     * Checks whether request includes attached files
     * http://php.net/manual/en/features.file-upload.multiple.php
     *
     * @param bool $onlySuccessful
     *
     * @return bool
     */
    public function hasFiles($onlySuccessful = false)
    {
        /**
         * @var $_FILES array
         */
        foreach ($_FILES as $file) {
            if (is_int($file['error'])) {
                $error = $file['error'];

                if (!$onlySuccessful || $error === UPLOAD_ERR_OK) {
                    return true;
                }
            } else {
                /** @noinspection ForeachSourceInspection */
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
     * @param bool $onlySuccessful
     *
     * @return \ManaPHP\Http\Request\File[]
     */
    public function getFiles($onlySuccessful = false)
    {
        $files = [];

        /**
         * @var $_FILES array
         */
        foreach ($_FILES as $key => $file) {
            if (is_int($file['error'])) {
                if (!$onlySuccessful || $file['error'] === UPLOAD_ERR_OK) {
                    $fileInfo = [
                        'name' => $file['name'],
                        'type' => $file['type'],
                        'tmp_name' => $file['tmp_name'],
                        'error' => $file['error'],
                        'size' => $file['size'],
                    ];
                    $files[] = new File($key, $fileInfo);
                }
            } else {
                $countFiles = count($file['error']);
                /** @noinspection ForeachInvariantsInspection */
                for ($i = 0; $i < $countFiles; $i++) {
                    if (!$onlySuccessful || $file['error'][$i] === UPLOAD_ERR_OK) {
                        $fileInfo = [
                            'name' => $file['name'][$i],
                            'type' => $file['type'][$i],
                            'tmp_name' => $file['tmp_name'][$i],
                            'error' => $file['error'][$i],
                            'size' => $file['size'][$i],
                        ];
                        $files[] = new File($key, $fileInfo);
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
     * @return string
     */
    public function getUrl()
    {
        $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        return strip_tags($url);
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return strip_tags($_SERVER['REQUEST_URI']);
    }

    /**
     * @return bool
     */
    public function hasAccessToken()
    {
        if ($this->has('access_token')) {
            return true;
        } elseif (isset($_SERVER['HTTP_X_ACCESS_TOKEN'])) {
            return true;
        } elseif ($this->hasHeader('Authorization')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string|null
     */
    public function getAccessToken()
    {
        if ($this->has('access_token')) {
            return $this->get('access_token');
        } elseif (isset($_SERVER['HTTP_X_ACCESS_TOKEN'])) {
            return $_SERVER['HTTP_X_ACCESS_TOKEN'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $parts = explode(' ', $_SERVER['HTTP_AUTHORIZATION'], 2);
            if ($parts[0] === 'Bearer' && count($parts) === 2) {
                return $parts[1];
            }
        }

        return null;
    }
}