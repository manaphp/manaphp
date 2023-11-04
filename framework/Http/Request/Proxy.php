<?php
declare(strict_types=1);

namespace ManaPHP\Http\Request;

use ArrayAccess;
use JsonSerializable;
use ManaPHP\Http\RequestInterface;

class Proxy implements ArrayAccess, JsonSerializable
{
    protected RequestInterface $request;
    protected string $type;

    public function __construct(RequestInterface $request, string $type)
    {
        $this->request = $request;
        $this->type = $type;
    }

    public function offsetExists(mixed $offset): bool
    {
        $context = $this->request->getContext();
        $type = $this->type;
        $global = $context->$type;

        return isset($global[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $context = $this->request->getContext();
        $type = $this->type;
        $global = $context->$type;

        return $global[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $context = $this->request->getContext();
        $type = $this->type;
        $global = &$context->$type;

        $global[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $context = $this->request->getContext();
        $type = $this->type;
        $global = &$context->$type;

        unset($global[$offset]);
    }

    public function __debugInfo(): array
    {
        $context = $this->request->getContext();
        $type = $this->type;

        return $context->$type;
    }

    public function jsonSerialize(): array
    {
        $context = $this->request->getContext();
        $type = $this->type;

        return $context->$type;
    }
}