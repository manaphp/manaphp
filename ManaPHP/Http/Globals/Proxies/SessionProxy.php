<?php

namespace ManaPHP\Http\Globals\Proxies;

use ArrayAccess;
use JsonSerializable;

class SessionProxy implements ArrayAccess, JsonSerializable
{
    /**
     * @var \ManaPHP\Http\SessionInterface
     */
    protected $_session;

    /**
     * SessionProxy constructor.
     *
     * @param \ManaPHP\Http\Request $request
     */
    public function __construct($request)
    {
        $this->_session = $request->getShared('session');
    }

    public function offsetExists($offset)
    {
        return $this->_session->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->_session->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->_session->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->_session->remove($offset);
    }

    public function __debugInfo()
    {
        return (array)$this->_session->get();
    }

    public function jsonSerialize()
    {
        return $this->_session->get();
    }
}