<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

class Matcher implements MatcherInterface
{
    protected string $handler;
    protected array $params;

    public function __construct(string $handler, array $params = [])
    {
        $this->handler = $handler;
        $this->params = $params;
    }

    public function getHandler(): string
    {
        return $this->handler;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}