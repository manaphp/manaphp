<?php

namespace ManaPHP\Socket;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class RequestContext
{
    /**
     * @var string
     */
    public $request_id;

    /**
     * @var array
     */
    public $_REQUEST = [];

    /**
     * @var array
     */
    public $_SERVER = [];

    public function __construct()
    {
        $this->request_id = 'aa' . bin2hex(random_bytes(15));
    }
}

/**
 * Class Request
 *
 * @package ManaPHP\Socket
 * @property-read \ManaPHP\Socket\RequestContext $_context
 */
class Request extends Component implements RequestInterface
{
    /**
     * @return \ManaPHP\Socket\RequestContext
     */
    public function getContext()
    {
        return $this->_context;
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
     * @return string
     */
    public function getClientIp()
    {
        $context = $this->_context;

        return $context->_SERVER['remote_ip'] ?? '';
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        return $this->_context->request_id;
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

        $this->_context->request_id = $request_id ?: 'aa' . bin2hex(random_bytes(15));
    }
}