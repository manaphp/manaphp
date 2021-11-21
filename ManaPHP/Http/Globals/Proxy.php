<?php

namespace ManaPHP\Http\Globals;

use ArrayAccess;
use JsonSerializable;

class Proxy implements ArrayAccess, JsonSerializable
{
    /**
     * @var \ManaPHP\Http\GlobalsInterface
     */
    protected $globals;

    /**
     * @var string
     */
    protected $type;

    /**
     * @param \ManaPHP\Http\GlobalsInterface $globals
     * @param string                         $type
     */
    public function __construct($globals, $type)
    {
        $this->globals = $globals;
        $this->type = $type;
    }

    public function offsetExists($offset)
    {
        $context = $this->globals->get();
        $type = $this->type;
        $global = $context->$type;

        return isset($global[$offset]);
    }

    public function offsetGet($offset)
    {
        $context = $this->globals->get();
        $type = $this->type;
        $global = $context->$type;

        return $global[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        $context = $this->globals->get();
        $type = $this->type;
        $global = &$context->$type;

        $global[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        $context = $this->globals->get();
        $type = $this->type;
        $global = &$context->$type;

        unset($global[$offset]);
    }

    public function __debugInfo()
    {
        $context = $this->globals->get();
        $type = $this->type;

        return $context->$type;
    }

    public function jsonSerialize()
    {
        $context = $this->globals->get();
        $type = $this->type;

        return $context->$type;
    }
}