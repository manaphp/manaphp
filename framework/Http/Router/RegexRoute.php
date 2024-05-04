<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

use JsonSerializable;
use function is_string;
use function preg_match;

class RegexRoute implements RouteInterface, JsonSerializable
{
    protected string $pattern;
    protected string $compiled;
    protected string $handler;
    protected string $method;

    public function __construct(string $method, string $pattern, string $compiled, string $handler)
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->compiled = $compiled;
        $this->handler = $handler;
    }

    public function match(string $uri, string $method = 'GET'): ?MatcherInterface
    {
        if ($method !== $this->method && $this->method !== '*') {
            return null;
        }

        if (preg_match($this->compiled, $uri, $matches) === 1) {
            $params = [];
            foreach ($matches as $k => $v) {
                if (is_string($k)) {
                    $params[$k] = $v;
                }
            }
            return new Matcher($this->handler, $params);
        } else {
            return null;
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'pattern'  => $this->pattern,
            'compiled' => $this->compiled,
            'handler'  => $this->handler,
            'method'   => $this->method,
        ];
    }
}
