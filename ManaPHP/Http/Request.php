<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Http\Request\File;

/**
 * Class ManaPHP\Http\Request
 *
 * @package request
 *
 * @property-read \ManaPHP\Http\FilterInterface $filter
 * @property-read \ManaPHP\DispatcherInterface  $dispatcher
 */
class Request extends Component implements RequestInterface
{
    public function __construct()
    {
        if (!$_POST && isset($_SERVER['REQUEST_METHOD']) && !in_array($_SERVER['REQUEST_METHOD'], ['GET', 'OPTIONS'], true)) {
            $data = file_get_contents('php://input');

            if (isset($_SERVER['CONTENT_TYPE'])
                && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
                $_POST = json_decode($data, true, 16);
            } else {
                parse_str($data, $_POST);
            }

            if (is_array($_POST)) {
                $_REQUEST = array_merge($_GET, $_POST);
            } else {
                $_POST = [];
            }
        }
    }

    /**
     *
     * @param array  $source
     * @param string $name
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return array|string|null
     */
    protected function _getHelper($source, $name = null, $rule = null, $default = '')
    {
        if (is_string($rule)) {
            if ($rule === '') {
                $default = '';
                $rule = null;
            }
        } elseif ($rule !== null) {
            $default = $rule;
            $rule = null;
        }

        if ($name === null) {
            return $source;
        } elseif ($current = strpos($name, '[')) {
            $value = $this->get();
            $var = substr($name, 0, $current);
            if (!isset($value[$var])) {
                return $default;
            }
            $value = $value[$var];
            while ($next = strpos($name, ']', $current)) {
                $var = substr($name, $current + 1, $next - $current - 1);
                if (!is_array($value) || !isset($value[$var])) {
                    return $default;
                }
                $value = $value[$var];
                $current = $next + 1;
            }
        } else {
            $value = (isset($source[$name]) && $source[$name] !== '') ? $source[$name] : $default;
        }

        if (is_array($value) && is_scalar($default)) {
            throw new InvalidValueException(['the value of `:name` name is not scalar', 'name' => $name]);
        }

        if ($rule === null || $rule === '') {
            return $value;
        }

        return $this->filter->sanitize($name, $rule, $value);
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
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($name = null, $rule = null, $default = '')
    {
        return $this->_getHelper($_REQUEST, $name, $rule, $default);
    }

    /**
     * @param string $name
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getGet($name = null, $rule = null, $default = '')
    {
        return $this->_getHelper($_GET, $name, $rule, $default);
    }

    /**
     * @param string $name
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getPost($name = null, $rule = null, $default = '')
    {
        return $this->_getHelper($_POST, $name, $rule, $default);
    }

    /**
     * Gets variable from $_SERVER
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getServer($name = null, $default = '')
    {
        if ($name === null) {
            return $_SERVER;
        } else {
            return isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
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
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getPut($name = null, $rule = null, $default = '')
    {
        return $this->_getHelper($_POST, $name, $rule, $default);
    }

    /**
     * @param string $name
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getQuery($name = null, $rule = null, $default = '')
    {
        return $this->_getHelper($_GET, $name, $rule, $default);
    }

    /**
     * @param string $name
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getInput($name = null, $rule = null, $default = '')
    {
        if ($name === null) {
            $params = $this->dispatcher->getParams();
            if (isset($params[0]) && count($params) === 1) {
                $params = ['id' => $params[0]];
            }
            $value = array_merge($this->get(), $params);
        } elseif ($this->dispatcher->hasParam($name)) {
            $value = $this->dispatcher->getParam($name);
        } else {
            $value = $this->get($name, $rule, $default);
        }

        if (is_int($default)) {
            $value = (int)$value;
        } elseif (is_string($default)) {
            $value = (string)$value;
        } elseif (is_float($default)) {
            $value = (double)$value;
        } elseif (is_bool($default)) {
            $value = (bool)$value;
        }

        return $value;
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
     * @param string $name
     *
     * @return bool
     */
    public function hasGet($name)
    {
        return isset($_GET[$name]);
    }

    /**
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
        return isset($_POST[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasQuery($name)
    {
        return isset($_GET[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasInput($name)
    {
        return $this->has($name) || $this->dispatcher->hasParam($name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasServer($name)
    {
        return isset($_SERVER[$name]);
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
        } else {
            return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        }
    }

    /**
     * Checks whether request has been made using ajax
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
     * @return string
     */
    public function getClientIp()
    {
        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } else {
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
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
     * Checks whether HTTP method is POST.
     *
     * @return bool
     */
    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Checks whether HTTP method is GET.
     *
     * @return bool
     */
    public function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Checks whether HTTP method is PUT.
     *
     * @return bool
     */
    public function isPut()
    {
        return $_SERVER['REQUEST_METHOD'] === 'PUT';
    }

    /**
     * Checks whether HTTP method is PATCH.
     *
     * @return bool
     */
    public function isPatch()
    {
        return $_SERVER['REQUEST_METHOD'] === 'PATCH';
    }

    /**
     * Checks whether HTTP method is HEAD.
     *
     * @return bool
     */
    public function isHead()
    {
        return $_SERVER['REQUEST_METHOD'] === 'HEAD';
    }

    /**
     * Checks whether HTTP method is DELETE.
     *
     * @return bool
     */
    public function isDelete()
    {
        return $_SERVER['REQUEST_METHOD'] === 'DELETE';
    }

    /**
     * Checks whether HTTP method is OPTIONS.
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
    public function hasFiles($onlySuccessful = true)
    {
        /** @var $_FILES array */
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
    public function getFiles($onlySuccessful = true)
    {
        $files = [];

        /** @var $_FILES array */
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
        $url = $this->getScheme() . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

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