<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

use ManaPHP\Exception\InvalidFormatException;
use ManaPHP\Helper\Str;
use function in_array;
use function is_string;
use function preg_match_all;
use function str_contains;

class Route implements RouteInterface
{
    protected string $pattern;
    protected string $compiled;
    protected string $handler;
    protected string $method;

    public function __construct(string $method, string $pattern, string $handler, bool $case_sensitive = true
    ) {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->compiled = $this->compilePattern($pattern, $case_sensitive);
        $this->handler = $handler;
    }

    protected function compilePattern(string $pattern, bool $case_sensitive): string
    {
        if (strpbrk($pattern, ':{') === false) {
            return $pattern;
        }

        $tr = [
            '{controller}' => '{controller:[a-zA-Z]\w*}',
            '{action}'     => '{action:[a-zA-Z]\w*}',
            '{id}'         => '{id:[^/]+}',
            ':int}'        => ':\d+}',
            ':uuid}'       => ':[A-Fa-f0-9]{8}(-[A-Fa-f0-9]{4}){3}-[A-Fa-f0-9]{12}}',
        ];
        $pattern = strtr($pattern, $tr);

        $need_restore_token = false;

        if (preg_match('#{\d#', $pattern) === 1) {
            $need_restore_token = true;
            $pattern = (string)preg_replace('#{([\d,]+)}#', '@\1@', $pattern);
        }

        $matches = [];
        if (preg_match_all('#{([A-Z].*)}#Ui', $pattern, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $parts = explode(':', $match[1], 2);
                $to = '(?<' . $parts[0] . '>' . ($parts[1] ?? '[^/]+') . ')';
                $pattern = (string)str_replace($match[0], $to, $pattern);
            }
        }

        if ($need_restore_token) {
            $pattern = (string)preg_replace('#@([\d,]+)@#', '{\1}', $pattern);
        }

        return '#^' . $pattern . '$#' . ($case_sensitive ? '' : 'i');
    }

    public function match(string $uri, string $method = 'GET'): ?MatcherInterface
    {
        $matches = [];

        if ($method !== $this->method && $this->method !== '*' && $this->method !== 'REST') {
            return null;
        }

        if ($this->compiled[0] !== '#') {
            if ($this->compiled === $uri) {
                return new Matcher($this->handler, []);
            } else {
                return null;
            }
        } else {
            $r = preg_match($this->compiled, $uri, $matches);
            if ($r === false) {
                throw new InvalidFormatException(['`{1}` is invalid for `{2}`', $this->compiled, $this->pattern]);
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
                    $controller = $parts['controller'] ?? '';
                    if ($controller !== '' && str_contains($this->pattern, '/{controller}')) {
                        $parts['controller'] = Str::singular($controller);
                    }

                    if (isset($matches['params'])) {
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
        }

        return new Matcher($this->handler, $parts);
    }
}
