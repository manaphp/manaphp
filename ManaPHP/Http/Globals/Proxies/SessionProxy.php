<?php

namespace ManaPHP\Http\Globals\Proxies;

use ArrayAccess;
use JsonSerializable;

class SessionProxy implements ArrayAccess, JsonSerializable
{
    /**
     * @var \ManaPHP\Http\Request
     */
    protected $_request;

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
        $this->_request = $request;
    }

    /**
     * @return \ManaPHP\Http\SessionInterface
     */
    protected function _getSession()
    {
        if (!$session = $this->_session ?? null) {
            return $this->_session = $this->_request->getDi()->getShared('session');
        } else {
            return $session;
        }
    }

    public function offsetExists($offset)
    {
        return $this->_getSession()->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->_getSession()->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->_getSession()->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->_getSession()->remove($offset);
    }

    public function __debugInfo()
    {
        return (array)$this->_getSession()->get();
    }

    public function jsonSerialize()
    {
        return $this->_getSession()->get();
    }
}