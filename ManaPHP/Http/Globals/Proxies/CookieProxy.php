<?php
namespace ManaPHP\Http\Globals\Proxies;

use ArrayAccess;
use ManaPHP\Exception\NotSupportedException;

class CookieProxy implements ArrayAccess
{
    /**
     * @var \ManaPHP\Http\Request
     */
    protected $_request;

    /**
     * CookieProxy constructor.
     *
     * @param \ManaPHP\Http\Request $request
     */
    public function __construct($request)
    {
        $this->_request = $request;
    }

    public function offsetExists($offset)
    {
        return isset($this->_request->_context->_COOKIE[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->_request->_context->_COOKIE[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new NotSupportedException(['please use $this->cookies->set to remove cookie `:name`', 'name' => $offset]);
    }

    public function offsetUnset($offset)
    {
        throw new NotSupportedException(['please use $this->cookies->delete to remove cookie `:name`', 'name' => $offset]);
    }

    public function __debugInfo()
    {
        return $this->_request->_context->_COOKIE;
    }
}