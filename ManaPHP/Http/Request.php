<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Coroutine\Context\Stickyable;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Http\Request\File\Exception as FileException;
use ManaPHP\Validator\ValidateFailedException;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class RequestContext implements Stickyable
{
    public $request_id;

    public $_GET = [];
    public $_POST = [];
    public $_REQUEST = [];
    public $_SERVER = [];
    public $_COOKIE = [];
    public $_FILES = [];

    public $rawBody;
}

/**
 * Class ManaPHP\Http\Request
 *
 * @package request
 *
 * @property-read \ManaPHP\DispatcherInterface $dispatcher
 * @property-read \ManaPHP\Http\RequestContext $_context
 */
class Request extends Component implements RequestInterface
{
    /**
     * @return \ManaPHP\Http\RequestContext
     */
    public function getContext()
    {
        return $this->_context;
    }

    /**
     * @param array  $GET
     * @param array  $POST
     * @param array  $SERVER
     * @param string $RAW_BODY
     * @param array  $COOKIE
     * @param array  $FILES
     *
     * @return void
     */
    public function prepare($GET, $POST, $SERVER, $RAW_BODY = null, $COOKIE = [], $FILES = [])
    {
        $context = $this->_context;

        if (!$POST
            && (isset($SERVER['REQUEST_METHOD']) && !in_array($SERVER['REQUEST_METHOD'], ['GET', 'OPTIONS'], true))
        ) {
            if (isset($SERVER['CONTENT_TYPE'])
                && str_contains($SERVER['CONTENT_TYPE'], 'application/json')
            ) {
                $POST = json_parse($RAW_BODY);
            } else {
                parse_str($RAW_BODY, $POST);
            }

            if (!is_array($POST)) {
                $POST = [];
            }
        }

        unset($POST['_url']);

        $context->_GET = $GET;
        $context->_POST = $POST;
        $context->_REQUEST = $POST === [] ? $GET : array_merge($GET, $POST);
        $context->_SERVER = $SERVER;
        $context->rawBody = $RAW_BODY;
        $context->_COOKIE = $COOKIE;
        $context->_FILES = $FILES;
    }

    /**
     * @return string
     */
    public function getRawBody()
    {
        return $this->_context->rawBody;
    }

    /**
     * @param array $params
     *
     * @return static
     */
    public function setParams($params)
    {
        $context = $this->_context;

        foreach ($params as $k => $v) {
            if (is_string($k)) {
                $context->_REQUEST[$k] = $v;
            }
        }

        if (isset($params[0])) {
            $context->_REQUEST['id'] = $params[0];
        }

        return $this;
    }

    /**
     * Gets a cookie
     *
     * @param string $name
     * @param string $default
     *
     * @return string|array
     */
    public function getCookie($name = null, $default = '')
    {
        $context = $this->_context;

        if ($name === null) {
            return $context->_COOKIE;
        } elseif (isset($context->_COOKIE[$name])) {
            return $context->_COOKIE[$name];
        } else {
            return $default;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasCookie($name)
    {
        $context = $this->_context;

        return isset($context->_COOKIE[$name]);
    }

    /**
     * @param string $field
     * @param mixed  $value
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function _normalizeValue($field, $value, $default)
    {
        $type = gettype($default);

        if ($type === 'string') {
            return (string)$value;
        } elseif ($type === 'integer') {
            return $this->validator->validateValue($field, $value, 'int');
        } elseif ($type === 'double') {
            return $this->validator->validateValue($field, $value, 'float');
        } elseif ($type === 'boolean') {
            return (bool)$this->validator->validateValue($field, $value, 'bool');
        } else {
            return $value;
        }
    }

    /**
     * Gets a variable from the $_REQUEST
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($name = null, $default = null)
    {
        $source = $this->_context->_REQUEST;

        if ($name === null) {
            return $source;
        }

        if (isset($source[$name]) && $source[$name] !== '') {
            $value = $source[$name];

            if (is_array($value) && is_scalar($default)) {
                throw new InvalidValueException(['the value of `:name` name is not scalar', 'name' => $name]);
            }

            return $default === null ? $value : $this->_normalizeValue($name, $value, $default);
        } elseif ($default === null) {
            return $this->validator->validateValue($name, null, ['required']);
        } else {
            return $default;
        }
    }

    /**
     * @param string $name
     *
     * @return int|string
     */
    public function getId($name = 'id')
    {
        $source = $this->_context->_REQUEST;

        if (isset($source[$name])) {
            $id = $source[$name];
        } elseif (isset($source['id'])) {
            $id = $source['id'];
        } else {
            throw new ValidateFailedException([$name => "$name field is required"]);
        }

        if (!is_scalar($id)) {
            throw new InvalidValueException('primary key value is not scalar');
        }

        return $id;
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
            return $context->_SERVER[$name] ?? $default;
        }
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->_context->_SERVER['REQUEST_METHOD'];
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
     * @return bool
     */
    public function isWebSocket()
    {
        return $this->getServer('HTTP_UPGRADE') === 'websocket';
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
     * @param int $max_len
     *
     * @return string
     */
    public function getUserAgent($max_len = -1)
    {
        $user_agent = $this->getServer('HTTP_USER_AGENT');

        return $max_len > 0 && strlen($user_agent) > $max_len ? substr($user_agent, 0, $max_len) : $user_agent;
    }

    /**
     * Checks whether HTTP method is POST.
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Checks whether HTTP method is GET.
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Checks whether HTTP method is PUT.
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * Checks whether HTTP method is PATCH.
     *
     * @return bool
     */
    public function isPatch()
    {
        return $this->getMethod() === 'PATCH';
    }

    /**
     * Checks whether HTTP method is HEAD.
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->getMethod() === 'HEAD';
    }

    /**
     * Checks whether HTTP method is DELETE.
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * Checks whether HTTP method is OPTIONS.
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->getMethod() === 'OPTIONS';
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

        /** @var array $_FILES */
        foreach ($context->_FILES as $file) {
            if (is_int($file['error'])) {
                $error = $file['error'];

                if (!$onlySuccessful || $error === UPLOAD_ERR_OK) {
                    return true;
                }
            } else {
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

        $r = [];

        /** @var array $_FILES */
        foreach ($context->_FILES as $key => $files) {
            if (isset($files[0])) {
                foreach ($files as $file) {
                    if (!$onlySuccessful || $file['error'] === UPLOAD_ERR_OK) {
                        $file['key'] = $key;

                        $r[] = $this->getInstance('ManaPHP\Http\Request\File', $file);
                    }
                }
            } elseif (is_int($files['error'])) {
                $file = $files;
                if (!$onlySuccessful || $file['error'] === UPLOAD_ERR_OK) {
                    $file['key'] = $key;

                    $r[] = $this->getInstance('ManaPHP\Http\Request\File', $file);
                }
            } else {
                $countFiles = count($files['error']);
                for ($i = 0; $i < $countFiles; $i++) {
                    if (!$onlySuccessful || $files['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'key'      => $key,
                            'name'     => $files['name'][$i],
                            'type'     => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error'    => $files['error'][$i],
                            'size'     => $files['size'][$i],
                        ];
                        $r[] = $this->getInstance('ManaPHP\Http\Request\File', $file);
                    }
                }
            }
        }

        return $r;
    }

    /**
     * @param string $key
     *
     * @return \ManaPHP\Http\Request\FileInterface
     */
    public function getFile($key = null)
    {
        $files = $this->getFiles();

        if ($key === null) {
            if ($files) {
                return current($files);
            } else {
                throw new FileException('can not found any uploaded files');
            }
        } else {
            foreach ($files as $file) {
                if ($file->getKey() === $key) {
                    return $file;
                }
            }
            throw new FileException(['can not found uploaded `:key` file', 'key' => $key]);
        }
    }

    /**
     * Gets web page that refers active request. ie: http://www.google.com
     *
     * @param int $max_len
     *
     * @return string
     */
    public function getReferer($max_len = -1)
    {
        $referer = $this->getServer('HTTP_REFERER');

        return $max_len > 0 && strlen($referer) > $max_len ? substr($referer, 0, $max_len) : $referer;
    }

    /**
     * @param bool $strict
     *
     * @return string
     */
    public function getOrigin($strict = true)
    {
        $context = $this->_context;

        if ($origin = $context->_SERVER['HTTP_ORIGIN'] ?? null) {
            return $origin;
        }

        if (!$strict && ($referer = $context->_SERVER['HTTP_REFERER'] ?? null)) {
            if ($pos = strpos($referer, '?')) {
                $referer = substr($referer, 0, $pos);
            }

            if ($pos = strpos($referer, '://')) {
                $pos = strpos($referer, '/', $pos + 3);
                return $pos ? substr($referer, 0, $pos) : $referer;
            }
        }

        return '';
    }

    /**
     * @return string
     */
    public function getHost()
    {
        $context = $this->_context;

        if ($host = $context->_SERVER['HTTP_HOST'] ?? null) {
            return $host;
        }

        return '';
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
     * @param string $name
     *
     * @return string|null
     */
    public function getToken($name = 'token')
    {
        if ($token = $this->get($name, '')) {
            return $token;
        } elseif ($token = $this->getServer('HTTP_AUTHORIZATION')) {
            $parts = explode(' ', $token, 2);
            if ($parts[0] === 'Bearer' && count($parts) === 2) {
                return $parts[1];
            }
        }

        return null;
    }

    public function jsonSerialize()
    {
        return $this->_context;
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        $context = $this->_context;

        if ($context->request_id === null) {
            $this->setRequestId($context->_SERVER['HTTP_X_REQUEST_ID'] ?? null);
        }

        return $context->request_id;
    }

    /**
     * @param string $request_id
     *
     * @return void
     */
    public function setRequestId($request_id = null)
    {
        if ($request_id !== null) {
            $request_id = preg_replace('#[^\-\w.]#', 'X', $request_id);
        }

        $this->_context->request_id = $request_id ?: bin2hex(random_bytes(16));
    }

    /**
     * @return float
     */
    public function getRequestTime()
    {
        $context = $this->_context;

        return $context->_SERVER['REQUEST_TIME_FLOAT'];
    }

    /**
     * @param int $precision
     *
     * @return float
     */
    public function getElapsedTime($precision = 3)
    {
        return round(microtime(true) - $this->getRequestTime(), $precision);
    }

    /**
     * @return string|null
     */
    public function getIfNoneMatch()
    {
        $context = $this->_context;

        return $context->_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
    }

    public function dump()
    {
        $data = parent::dump();

        if (DIRECTORY_SEPARATOR === '\\') {
            foreach (['PATH', 'SystemRoot', 'COMSPEC', 'PATHEXT', 'WINDIR'] as $name) {
                unset($data['_context']['_SERVER'][$name]);
            }
        }

        return $data;
    }
}