<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Http\Request\File;

class RequestContext
{
    public $_GET = [];
    public $_POST = [];
    public $_REQUEST = [];
    public $_SERVER = [];
    public $_COOKIE = [];
    public $_FILES = [];
    public $_SESSION;
}

/**
 * Class ManaPHP\Http\Request
 *
 * @package request
 *
 * @property-read \ManaPHP\Http\FilterInterface $filter
 * @property-read \ManaPHP\DispatcherInterface  $dispatcher
 * @property \ManaPHP\Http\RequestContext       $_context
 */
class Request extends Component implements RequestInterface
{
    public function __construct()
    {
        $context = $this->_context;

        if (!$context->_POST && isset($context->_SERVER['REQUEST_METHOD']) && !in_array($context->_SERVER['REQUEST_METHOD'], ['GET', 'OPTIONS'], true)) {
            $data = file_get_contents('php://input');

            if (isset($context->_SERVER['CONTENT_TYPE'])
                && strpos($context->_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
                $context->_POST = json_decode($data, true, 16);
            } else {
                parse_str($data, $context->_POST);
            }

            if (is_array($context->_POST)) {
                $context->_REQUEST = $context->_POST + $context->_GET;
            } else {
                $context->_POST = [];
            }
        }
    }

    /**
     * @return \ManaPHP\Http\RequestContext
     */
    public function getGlobals()
    {
        return $this->_context;
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
        $context = $this->_context;

        return $this->_getHelper($context->_REQUEST, $name, $rule, $default);
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
        $context = $this->_context;

        return $this->_getHelper($context->_GET, $name, $rule, $default);
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
        $context = $this->_context;

        return $this->_getHelper($context->_POST, $name, $rule, $default);
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
        $context = $this->_context;
	
        if ($name === null) {
            return $context->_SERVER;
        } else {
            return isset($context->_SERVER[$name]) ? $context->_SERVER[$name] : $default;
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
        $context = $this->_context;

        return $this->_getHelper($context->_POST, $name, $rule, $default);
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
        $context = $this->_context;

        return $this->_getHelper($context->_GET, $name, $rule, $default);
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
        $context = $this->_context;

        return isset($context->_REQUEST[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasGet($name)
    {
        $context = $this->_context;

        return isset($context->_GET[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasPost($name)
    {
        $context = $this->_context;

        return isset($context->_POST[$name]);
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
        return $this->hasPost($name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasQuery($name)
    {
        return $this->hasGet($name);
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
        $context = $this->_context;

        return isset($context->_SERVER[$name]);
    }

    /**
     * Gets HTTP schema (http/https)
     *
     * @return string
     */
    public function getScheme()
    {
        if ($scheme = $this->getServer('REQUEST_SCHEME')) {
            return $scheme;
        } else {
            return $this->getServer('HTTPS') === 'on' ? 'https' : 'http';
        }
    }

    /**
     * Checks whether request has been made using ajax
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->getServer('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    /**
     * @return string
     */
    public function getClientIp()
    {
        return $this->getServer('HTTP_X_REAL_IP') ?: $this->getServer('REMOTE_ADDR');
    }

    /**
     * Gets HTTP user agent used to made the request
     *
     * @return string
     */
    public function getUserAgent()
    {
        return strip_tags($this->getServer('HTTP_USER_AGENT'));
    }

    /**
     * Checks whether HTTP method is POST.
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->getServer('REQUEST_METHOD') === 'POST';
    }

    /**
     * Checks whether HTTP method is GET.
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->getServer('REQUEST_METHOD') === 'GET';
    }

    /**
     * Checks whether HTTP method is PUT.
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->getServer('REQUEST_METHOD') === 'PUT';
    }

    /**
     * Checks whether HTTP method is PATCH.
     *
     * @return bool
     */
    public function isPatch()
    {
        return $this->getServer('REQUEST_METHOD') === 'PATCH';
    }

    /**
     * Checks whether HTTP method is HEAD.
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->getServer('REQUEST_METHOD') === 'HEAD';
    }

    /**
     * Checks whether HTTP method is DELETE.
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->getServer('REQUEST_METHOD') === 'DELETE';
    }

    /**
     * Checks whether HTTP method is OPTIONS.
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->getServer('REQUEST_METHOD') === 'OPTIONS';
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
        $context = $this->_context;

        /** @var $_FILES array */
        foreach ($context->_FILES as $file) {
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
        $context = $this->_context;

        $files = [];

        /** @var $_FILES array */
        foreach ($context->_FILES as $key => $file) {
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
        return strip_tags($this->getServer('HTTP_REFERER'));
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return strip_tags($this->getScheme() . '://' . $this->getServer('HTTP_HOST') . $this->getServer('REQUEST_URI'));
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return strip_tags($this->getServer('REQUEST_URI'));
    }

    /**
     * @return string|null
     */
    public function getAccessToken()
    {
        if ($token = $this->get('access_token')) {
            return $token;
        } elseif ($token = $this->getServer('HTTP_AUTHORIZATION')) {
            $parts = explode(' ', $token, 2);
            if ($parts[0] === 'Bearer' && count($parts) === 2) {
                return $parts[1];
            }
        }

        return null;
    }
}