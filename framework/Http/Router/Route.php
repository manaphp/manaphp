<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

use ManaPHP\Exception\InvalidFormatException;
use function in_array;
use function is_string;
use function str_contains;

class Route implements RouteInterface
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
        $matches = [];

        if ($method !== $this->method && $this->method !== '*' && $this->method !== 'REST') {
            return null;
        }

        $r = preg_match($this->compiled, $uri, $matches);
        if ($r === false) {
            throw new InvalidFormatException(['`{1}` is invalid', $this->compiled]);
        } elseif ($r === 1) {
            $parts = [];
            foreach ($matches as $k => $v) {
                if (is_string($k)) {
                    if (str_contains($v, '_')
                        && in_array($k, ['area', 'controller', 'action'], true)
                        && preg_match('#_$|_\w$|_\w_#', $v) === 1
                    ) {
                        return null;
                    }

                    $parts[$k] = $v;
                }
            }

            if ($this->method === 'REST') {
                if (isset($matches['id'])) {
                    $m2a = ['GET' => 'detail', 'POST' => 'update', 'PUT' => 'update', 'DELETE' => 'delete'];
                } else {
                    $m2a = ['GET' => 'index', 'POST' => 'create'];
                }
                if (isset($m2a[$method])) {
                    $parts['action'] = $m2a[$method];
                } else {
                    return null;
                }
            }
        } else {
            return null;
        }

        return new Matcher($this->handler, $parts);
    }
}
