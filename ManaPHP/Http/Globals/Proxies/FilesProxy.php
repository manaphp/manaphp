<?php

namespace ManaPHP\Http\Globals\Proxies;

use ArrayAccess;
use JsonSerializable;

class FilesProxy implements ArrayAccess, JsonSerializable
{
    /**
     * @var \ManaPHP\Http\Request
     */
    protected $_request;

    /**
     * FilesProxy constructor.
     *
     * @param \ManaPHP\Http\Request $request
     */
    public function __construct($request)
    {
        $this->_request = $request;
    }

    public function offsetExists($offset)
    {
        return isset($this->_request->_context->_FILES[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->_request->_context->_FILES[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->_request->_context->_FILES[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->_request->_context->_FILES[$offset]);
    }

    public function __debugInfo()
    {
        return $this->_request->_context->_FILES;
    }

    public function jsonSerialize()
    {
        return $this->_request->_context->_FILES;
    }
}