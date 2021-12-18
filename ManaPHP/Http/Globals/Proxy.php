<?php
declare(strict_types=1);

namespace ManaPHP\Http\Globals;

use ArrayAccess;
use JsonSerializable;
use ManaPHP\Http\GlobalsInterface;

class Proxy implements ArrayAccess, JsonSerializable
{
    protected GlobalsInterface $globals;
    protected string $type;

    public function __construct(GlobalsInterface $globals, string $type)
    {
        $this->globals = $globals;
        $this->type = $type;
    }

    public function offsetExists(mixed $offset): bool
    {
        $context = $this->globals->get();
        $type = $this->type;
        $global = $context->$type;

        return isset($global[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $context = $this->globals->get();
        $type = $this->type;
        $global = $context->$type;

        return $global[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $context = $this->globals->get();
        $type = $this->type;
        $global = &$context->$type;

        $global[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $context = $this->globals->get();
        $type = $this->type;
        $global = &$context->$type;

        unset($global[$offset]);
    }

    public function __debugInfo(): array
    {
        $context = $this->globals->get();
        $type = $this->type;

        return $context->$type;
    }

    public function jsonSerialize(): array
    {
        $context = $this->globals->get();
        $type = $this->type;

        return $context->$type;
    }
}