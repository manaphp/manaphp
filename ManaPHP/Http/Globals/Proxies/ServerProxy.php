<?php

namespace ManaPHP\Http\Globals\Proxies;

use ArrayAccess;
use JsonSerializable;

class ServerProxy implements ArrayAccess, JsonSerializable
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
        return isset($this->request->context->_SERVER[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->request->context->_SERVER[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->request->context->_SERVER[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->request->context->_SERVER[$offset]);
    }

    public function __debugInfo()
    {
        return $this->request->context->_SERVER;
    }

    public function jsonSerialize()
    {
        return $this->request->context->_SERVER;
    }
}