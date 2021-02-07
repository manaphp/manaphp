<?php

namespace ManaPHP\Http\Globals\Proxies;

use ArrayAccess;
use JsonSerializable;

class RequestProxy implements ArrayAccess, JsonSerializable
{
    /**
     * @var \ManaPHP\Http\Request
     */
    protected $request;

    /**
     * @param \ManaPHP\Http\Request $request
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    public function offsetExists($offset)
    {
        return isset($this->request->context->_REQUEST[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->request->context->_REQUEST[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->request->context->_REQUEST[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->request->context->_REQUEST[$offset]);
    }

    public function __debugInfo()
    {
        return $this->request->context->_REQUEST;
    }

    public function jsonSerialize()
    {
        return $this->request->context->_REQUEST;
    }
}