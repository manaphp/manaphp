<?php

namespace ManaPHP\Http\Globals\Proxies;

use ArrayAccess;
use JsonSerializable;

class SessionProxy implements ArrayAccess, JsonSerializable
{
    /**
     * @var \ManaPHP\Http\SessionInterface
     */
    protected $session;

    /**
     * @param \ManaPHP\Http\SessionInterface $session
     */
    public function __construct($session)
    {
        $this->session = $session;
    }

    public function offsetExists($offset)
    {
        return $this->session->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->session->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->session->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->session->remove($offset);
    }

    public function __debugInfo()
    {
        return (array)$this->session->get();
    }

    public function jsonSerialize()
    {
        return $this->session->get();
    }
}