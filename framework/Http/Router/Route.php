<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

use ManaPHP\Exception\InvalidFormatException;
use ManaPHP\Helper\Str;

class Route implements RouteInterface
{
    protected string $pattern;
    protected string $compiled;
    protected array $paths;
    protected string|array $methods;

    public function __construct(string $pattern, string|array $paths = [], string|array $methods = [],
        bool $case_sensitive = true
    ) {
        $this->pattern = $pattern;
        $this->compiled = $this->compilePattern($pattern, $case_sensitive);
        $this->paths = $this->normalizePaths($paths);
        $this->methods = $methods;
    }

    protected function compilePattern(string $pattern, bool $case_sensitive): string
    {
        if (strpbrk($pattern, ':{') === false) {
            return $pattern;
        }

        $tr = [
            '{area}'       => '{area:[a-zA-Z]\w*}',
            ':area'        => '{area:[a-zA-Z]\w*}',
            '{controller}' => '{controller:[a-zA-Z]\w*}',
            ':controller'  => '{controller:[a-zA-Z]\w*}',
            '{action}'     => '{action:[a-zA-Z]\w*}',
            ':action'      => '{action:[a-zA-Z]\w*}',
            '{params}'     => '{params:.*}',
            ':params'      => '{params:.*}',
            '{id}'         => '{id:[^/]+}',
            ':id'          => '{id:[^/]+}',
            ':int}'        => ':\d+}',
            ':uuid}'       => ':[A-Fa-f0-9]{8}(-[A-Fa-f0-9]{4}){3}-[A-Fa-f0-9]{12}}',
        ];
        $pattern = strtr($pattern, $tr);

        if (str_contains($pattern, '/:')) {
            $pattern = preg_replace('#/:(\w+)#', '/{\1}', $pattern);
        }

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

    protected function normalizePaths(string|array $paths): array
    {
        $routePaths = [];

        if (is_string($paths)) {
            if (($pos = strpos($paths, '::')) !== false) {
                $routePaths['controller'] = substr($paths, 0, $pos);
                $routePaths['action'] = substr($paths, $pos + 2);
            } elseif (($pos = strpos($paths, '@')) !== false) {
                $routePaths['controller'] = basename(substr($paths, 0, $pos), 'Controller');
                $routePaths['action'] = substr($paths, $pos + 1);
            } elseif (str_starts_with($paths, '/')) {
                $parts = explode('/', substr($paths, 1));
                if (count($parts) === 3) {
                    $routePaths['area'] = $parts[0];
                    $routePaths['controller'] = $parts[1];
                    $routePaths['action'] = $parts[2] === '' ? 'index' : $parts[2];
                } else {
                    $routePaths['controller'] = $parts[0];
                    $routePaths['action'] = $parts[1] === '' ? 'index' : $parts[1];
                }
            } else {
                $routePaths['controller'] = $paths;
                $routePaths['action'] = 'index';
            }
        } elseif (is_array($paths)) {
            if (isset($paths['area'])) {
                $routePaths['area'] = $paths['area'];
            }

            if (isset($paths['controller'])) {
                $routePaths['controller'] = $paths['controller'];
            } elseif (isset($paths[0])) {
                $routePaths['controller'] = $paths[0];
            } else {
                $routePaths['controller'] = 'index';
            }

            if (isset($paths['action'])) {
                $routePaths['action'] = $paths['action'];
            } elseif (isset($paths[1])) {
                $routePaths['action'] = $paths[1];
            } else {
                $routePaths['action'] = 'index';
            }

            $params = [];
            foreach ($paths as $k => $v) {
                if (is_string($k) && !in_array($k, ['area', 'controller', 'action'], true)) {
                    $params[$k] = $v;
                }
            }

            if ($params) {
                $routePaths['params'] = $params;
            }
        }

        if (isset($routePaths['controller']) && str_contains($routePaths['controller'], '\\')) {
            $controller = strtr($routePaths['controller'], '\\', '/');
            /** @noinspection RegExpUnnecessaryNonCapturingGroup */
            $pattern = '#(?:/Controllers/([^/]+)/(\w+)Controller)|(?:/Areas/([^/]+)/Controllers/(\w+)Controller)$#';

            if (substr_count($controller, '/') === 2) {
                $routePaths['controller'] = basename($controller, 'Controller');
            } elseif (preg_match($pattern, $controller, $match)) {
                if (isset($match[3])) {
                    $routePaths['area'] = $match[3];
                    $routePaths['controller'] = $match[4];
                } else {
                    $routePaths['area'] = $match[1];
                    $routePaths['controller'] = $match[2];
                }
            } else {
                $routePaths['controller'] = basename($controller, 'Controller');
            }
        }

        return $routePaths;
    }

    public function match(string $uri, string $method = 'GET'): ?array
    {
        $matches = [];

        $methods = $this->methods;
        if ($methods === [] || $methods === 'REST') {
            null;
        } elseif (is_string($methods)) {
            if ($methods !== $method) {
                return null;
            }
        } elseif (!in_array($method, $methods, true)) {
            return null;
        }

        if ($this->compiled[0] !== '#') {
            if ($this->compiled === $uri) {
                return $this->paths;
            } else {
                return null;
            }
        } else {
            $r = preg_match($this->compiled, $uri, $matches);
            if ($r === false) {
                throw new InvalidFormatException(['`{1}` is invalid for `{2}`', $this->compiled, $this->pattern]);
            } elseif ($r === 1) {
                $parts = $this->paths;

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

                if ($this->methods === 'REST') {
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

        if (isset($parts['action']) && preg_match('#^\d#', $parts['action'])) {
            $parts['params'] = $parts['action'];

            $m2a = ['GET' => 'detail', 'POST' => 'edit', 'DELETE' => 'delete'];
            if (!isset($m2a[$method])) {
                return null;
            }
            $parts['action'] = $m2a[$method];
        }

        $r = [];
        $r['controller'] = $parts['controller'] ?? 'index';
        if (isset($parts['area'])) {
            $r['area'] = $parts['area'];
        }

        $r['action'] = $parts['action'] ?? 'index';
        $params = $parts['params'] ?? '';

        unset($parts['area'], $parts['controller'], $parts['action'], $parts['params']);
        if ($params) {
            $parts[0] = $params;
        }
        $r['params'] = $parts;

        return $r;
    }
}
