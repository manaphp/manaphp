<?php

namespace ManaPHP\Http\Router;

use ManaPHP\Exception\InvalidFormatException;
use ManaPHP\Helper\Str;

class Route implements RouteInterface
{
    /**
     * @var string
     */
    protected $_pattern;

    /**
     * @var string
     */
    protected $_compiled;

    /**
     * @var array
     */
    protected $_paths;

    /**
     * @var string
     */
    protected $_method;

    /**
     * @param string       $pattern
     * @param string|array $paths
     * @param string       $method
     * @param bool         $case_sensitive
     */
    public function __construct($pattern, $paths = null, $method = null, $case_sensitive = true)
    {
        $this->_pattern = $pattern;
        if ($method === 'REST') {
            $this->_compiled = $this->_compilePattern(($pattern . '(/{params:[-\w]+})?'), $case_sensitive);
        } else {
            $this->_compiled = $this->_compilePattern($pattern, $case_sensitive);
        }

        $this->_paths = $this->_normalizePaths($paths);
        $this->_method = $method;
    }

    /**
     * Replaces placeholders from pattern returning a valid PCRE regular expression
     *
     * @param string $pattern
     * @param bool   $case_sensitive
     *
     * @return string
     */
    protected function _compilePattern($pattern, $case_sensitive)
    {
        if (str_contains($pattern, '{')) {
            $tr = [
                '{area}'       => '{area:[a-zA-Z]\w*}',
                '{controller}' => '{controller:[a-zA-Z]\w*}',
                '{action}'     => '{action:[a-zA-Z]\w*}',
                '{params}'     => '{params:.*}',
                '{id}'         => '{id:[^/]+}',
                ':int}'        => ':\d+}',
                ':uuid}'       => ':[A-Fa-f0-9]{8}(-[A-Fa-f0-9]{4}){3}-[A-Fa-f0-9]{12}}',
            ];
            $pattern = strtr($pattern, $tr);
        }

        if (str_contains($pattern, '/:')) {
            $pattern = preg_replace('#/:(\w+)#', '/{\1}', $pattern);
        }

        if (str_contains($pattern, '{')) {
            $need_restore_token = false;

            if (preg_match('#{\d#', $pattern) === 1) {
                $need_restore_token = true;
                $pattern = (string)preg_replace('#{([\d,]+)}#', '@\1@', $pattern);
            }

            $matches = [];
            if (preg_match_all('#{([A-Z].*)}#Ui', $pattern, $matches, PREG_SET_ORDER) > 0) {
                foreach ($matches as $match) {
                    $parts = explode(':', $match[1], 2);
                    $to = '(?<' . $parts[0] . '>' . ($parts[1] ?? '[\w\-]+') . ')';
                    $pattern = (string)str_replace($match[0], $to, $pattern);
                }
            }

            if ($need_restore_token) {
                $pattern = (string)preg_replace('#@([\d,]+)@#', '{\1}', $pattern);
            }

            return '#^' . $pattern . '$#' . ($case_sensitive ? '' : 'i');
        } else {
            return $pattern;
        }
    }

    /**
     * Returns routePaths
     *
     * @param string|array $paths
     *
     * @return array
     */
    protected function _normalizePaths($paths = [])
    {
        $routePaths = [];

        if ($paths === null) {
            return ['controller' => 'index', 'action' => 'index'];
        } elseif (is_string($paths)) {
            if (($pos = strpos($paths, '::')) !== false) {
                $routePaths['controller'] = substr($paths, 0, $pos);
                $routePaths['action'] = substr($paths, $pos + 2);
            } elseif (($pos = strpos($paths, '@')) !== false) {
                $routePaths['controller'] = basename(substr($paths, 0, $pos), 'Controller');
                $routePaths['action'] = substr($paths, $pos + 1);
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

    /**
     * @param string $uri
     * @param string $method
     *
     * @return array|false
     */
    public function match($uri, $method = 'GET')
    {
        $matches = [];

        if ($this->_method !== null && $this->_method !== $method && $this->_method !== 'REST') {
            return false;
        }

        if ($this->_compiled[0] !== '#') {
            if ($this->_compiled === $uri) {
                return $this->_paths;
            } else {
                return false;
            }
        } else {
            $r = preg_match($this->_compiled, $uri, $matches);
            if ($r === false) {
                throw new InvalidFormatException(['`%s` is invalid for `%s`', $this->_compiled, $this->_pattern]);
            } elseif ($r === 1) {
                $parts = $this->_paths;

                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        if (str_contains($v, '_')
                            && in_array($k, ['area', 'controller', 'action'], true)
                            && preg_match('#_$|_\w$|_\w_#', $v) === 1
                        ) {
                            return false;
                        }

                        $parts[$k] = $v;
                    }
                }

                if ($this->_method === 'REST') {
                    $controller = $parts['controller'] ?? '';
                    if ($controller !== '' && str_contains($this->_pattern, '/{controller}')) {
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
                        return false;
                    }
                }
            } else {
                return false;
            }
        }

        if (isset($parts['action']) && preg_match('#^\d#', $parts['action'])) {
            $parts['params'] = $parts['action'];

            $m2a = ['GET' => 'detail', 'POST' => 'edit', 'DELETE' => 'delete'];
            if (!isset($m2a[$method])) {
                return false;
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
