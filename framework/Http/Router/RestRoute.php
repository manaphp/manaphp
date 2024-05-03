<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

use function is_string;
use function preg_match;

class RestRoute implements RouteInterface
{
    protected string $pattern;
    protected string $compiled;
    protected string $handler;

    public function __construct(string $pattern, string $compiled, string $handler)
    {
        $this->pattern = $pattern;
        $this->compiled = $compiled;
        $this->handler = $handler;
    }

    public function match(string $uri, string $method = 'GET'): ?MatcherInterface
    {
        if ((preg_match($this->compiled, $uri, $matches)) === 1) {
            $params = [];
            foreach ($matches as $k => $v) {
                if (is_string($k)) {
                    $params[$k] = $v;
                }
            }

            if (isset($matches['id'])) {
                $m2a = ['GET' => 'detail', 'POST' => 'update', 'PUT' => 'update', 'DELETE' => 'delete'];
            } else {
                $m2a = ['GET' => 'index', 'POST' => 'create'];
            }

            if (isset($m2a[$method])) {
                $params['action'] = $m2a[$method];
                return new Matcher($this->handler, $params);
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}
