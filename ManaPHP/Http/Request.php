<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Request\Exception as RequestException;
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
 * @property \ManaPHP\Http\FilterInterface $filter
 */
class Request extends Component implements RequestInterface
{
    /**
     * @var string
     */
    protected $_rawBody;

    /**
     * @var array
     */
    protected $_putCache;

    /**
     * @var string
     */
    protected $_clientAddress;

    /**
     *
     * @param array  $source
     * @param string $name
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return string|null
     * @throws \ManaPHP\Http\Request\Exception
     */
    protected function _getHelper($source, $name = null, $rule = null, $defaultValue = null)
    {
        if ($name === null) {
            $data = [];
            foreach ($source as $k => $_) {
                $data[$k] = $this->_getHelper($source, $k, $rule);
            }
            return $data;
        }

        return $this->filter->sanitize($name, $rule, isset($source[$name]) ? $source[$name] : $defaultValue);
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
    public function get($name = null, $rule = null, $defaultValue = null)
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
    public function getGet($name = null, $rule = null, $defaultValue = null)
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
    public function getPost($name = null, $rule = null, $defaultValue = null)
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
    public function getServer($name = null, $defaultValue = null)
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
    public function getPut($name = null, $rule = null, $defaultValue = null)
    {
        if ($this->_putCache === null && $this->isPut()) {
            parse_str($this->getRawBody(), $this->_putCache);
        }

        return $this->_getHelper($this->_putCache, $name, $rule, $defaultValue);
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
    public function getQuery($name = null, $rule = null, $defaultValue = null)
    {
        return $this->_getHelper($_GET, $name, $rule, $defaultValue);
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
     * @return string
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getHeader($name)
    {
        $name = strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }

        $name = 'HTTP_' . $name;
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }

        return null;
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
            throw new RequestException('`HTTPS` field not exists in $_SERVER'/**m0b994a4143d072cff*/);
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
        if ($this->_rawBody === null) {
            $this->_rawBody = file_get_contents('php://input');
        }

        return $this->_rawBody;
    }

    /**
     * @param bool $assoc
     *
     * @return array|\stdClass
     * @throws \ManaPHP\Http\Request\Exception
     */
    public function getJsonBody($assoc = true)
    {
        $r = json_decode(file_get_contents('php://input'), $assoc);

        if ($r === null) {
            throw new RequestException('json_decode raw body failed.');
        }

        return $r;
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
                if (Text::startsWith($client_address, '127.0.') || Text::startsWith($client_address,
                        '192.168.') || Text::startsWith($client_address, '10.')
                ) {
                    $this->_clientAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } else {
                    $this->_clientAddress = $_SERVER['REMOTE_ADDR'];
                }
            }
        }

        return $this->_clientAddress;
    }

    /**set the client address for getClientAddress method
     *
     * @param string|callable
     */
    public function setClientAddress($address)
    {
        if (is_string($address)) {
            $this->_clientAddress = $address;
        } else {
            $this->_clientAddress = $address();
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

    /**
     * @return string|null
     * @throws \ManaPHP\Http\Request\Exception
     */
    public function getAccessToken()
    {
        if ($this->has('access_token')) {
            return $this->get('access_token');
        } elseif ($this->hasServer('X_ACCESS_TOKEN')) {
            return $this->getServer('X_ACCESS_TOKEN');
        } else {
            $authorization = null;
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                if (isset($headers['Authorization'])) {
                    $authorization = $headers['Authorization'];
                }
            } else {
                $authorization = $this->getHeader('Authorization');
            }

            if ($authorization) {
                $parts = explode(' ', $authorization, 2);
                if ($parts[0] === 'Bearer' && count($parts) === 2) {
                    return $parts[1];
                }
            }
        }

        return null;
    }
}