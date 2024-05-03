<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

use ManaPHP\Exception\InvalidFormatException;
use function is_string;

class RegexRoute implements RouteInterface
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

        $r = preg_match($this->compiled, $uri, $matches);
        if ($r === false) {
            throw new InvalidFormatException(['`{1}` is invalid', $this->compiled]);
        } elseif ($r === 1) {
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
}
