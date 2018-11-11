<?php

namespace ManaPHP\Router;

use ManaPHP\Exception\InvalidFormatException;

/**
 * Class ManaPHP\Router\Route
 *
 * @package router
 */
class Route implements RouteInterface
{
    /**
     * @var string
     */
    protected $_pattern;

    /**
     * @var string
     */
    protected $_compiledPattern;

    /**
     * @var array
     */
    protected $_paths;

    /**
     * @var string
     */
    protected $_method;

    /**
     * \ManaPHP\Router\Route constructor
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string       $method
     */
    public function __construct($pattern, $paths = null, $method = null)
    {
        $this->_pattern = $pattern;
        $this->_compiledPattern = $this->_compilePattern($method !== 'REST' ? $pattern : ($pattern . '(/{params:[a-z0-9_-]+})?'));
        $this->_paths = $this->_getRoutePaths($paths);
        $this->_method = $method;
    }

    /**
     * Replaces placeholders from pattern returning a valid PCRE regular expression
     *
     * @param string $pattern
     *
     * @return string
     */
    protected function _compilePattern($pattern)
    {
        // If a pattern contains ':', maybe there are placeholders to replace
        if (strpos($pattern, ':') !== false) {
            $tr = [
                '/:area' => '/({area:[a-z\d_-]*})?',
                '/:controller' => '(/{controller:[a-z\d_-]*})?',
                '/:action' => '(/{action:[a-z\d_-]*})?',
                '/:params' => '(/{params:.*})?',
                ':int' => ':\d+',
            ];
            $pattern = strtr($pattern, $tr);
        }

        if (strpos($pattern, '{') !== false) {
            $need_restore_token = false;

            if (preg_match('#{\d#', $pattern) === 1) {
                $need_restore_token = true;
                $pattern = (string)preg_replace('#{([\d,]+)}#', '@\1@', $pattern);
            }

            $matches = [];
            if (preg_match_all('#{([A-Z].*)}#Ui', $pattern, $matches, PREG_SET_ORDER) > 0) {
                foreach ($matches as $match) {
                    $parts = explode(':', $match[1], 2);
                    $to = '(?<' . $parts[0] . '>' . (isset($parts[1]) ? $parts[1] : '[\w-]+') . ')';
                    $pattern = str_replace($match[0], $to, $pattern);
                }
            }

            if ($need_restore_token) {
                $pattern = (string)preg_replace('#@([\d,]+)@#', '{\1}', $pattern);
            }

            return '#^' . $pattern . '$#i';
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
    public function _getRoutePaths($paths = [])
    {
        $routePaths = [];

        if (is_string($paths)) {
            if (($pos = strpos($paths, '::')) !== false) {
                $routePaths['controller'] = substr($paths, 0, $pos);
                $routePaths['action'] = substr($paths, $pos + 2);
            } else {
                $routePaths['controller'] = $paths;
            }
        } elseif (is_array($paths)) {
            if (isset($paths[0])) {
                if (($pos = strpos($paths[0], '::')) !== false) {
                    $routePaths['controller'] = substr($paths[0], 0, $pos);
                    $routePaths['action'] = substr($paths[0], $pos + 2);
                } else {
                    $routePaths['controller'] = $paths[0];
                }
            }

            if (isset($paths[1])) {
                $routePaths['action'] = $paths[1];
            }

            /** @noinspection ForeachSourceInspection */
            foreach ($paths as $k => $v) {
                if (is_string($k)) {
                    $routePaths[$k] = $v;
                }
            }
        }

        if (isset($routePaths['controller']) && strpos($routePaths['controller'], '\\') !== false) {
            $controller = $routePaths['controller'];
            $routePaths['controller'] = basename(strtr($controller, '\\', '/'), 'Controller');
            if (($pos = strpos($controller, '\Areas\\')) !== false) {
                $pos2 = strpos($controller, '\\', $pos + 7);
                $routePaths['area'] = substr($controller, $pos + 7, $pos2 - $pos - 7);
            }
        }

        return $routePaths;
    }

    /**
     * @param string $uri
     * @param string $method
     *
     * @return bool|array
     */
    public function match($uri, $method = 'GET')
    {
        $matches = [];

        if ($this->_method !== null && $this->_method !== $method && $this->_method !== 'REST') {
            return false;
        }

        if ($this->_compiledPattern[0] !== '#') {
            if ($this->_compiledPattern === $uri) {
                $parts = $this->_paths;
            } else {
                return false;
            }
        } else {
            $r = preg_match($this->_compiledPattern, $uri, $matches);
            if ($r === false) {
                throw new InvalidFormatException([
                    '`:compiled` pcre pattern is invalid for `:pattern`',
                    'compiled' => $this->_compiledPattern,
                    'pattern' => $this->_pattern
                ]);
            } elseif ($r === 1) {
                $parts = $this->_paths;

                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        $parts[$k] = $v;
                    }
                }

                if ($this->_method === 'REST') {
                    if (isset($matches['params'])) {
                        $methodAction = ['GET' => 'detail', 'POST' => 'update', 'PUT' => 'update', 'DELETE' => 'delete'];
                    } else {
                        $methodAction = ['GET' => 'list', 'POST' => 'create'];
                    }
                    if (isset($methodAction[$method])) {
                        $parts['action'] = $methodAction[$method];
                    } else {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }

        if (isset($parts['controller']) && $parts['controller'] === '') {
            unset($parts['controller']);
        }

        if (isset($parts['action']) && $parts['action'] === '') {
            unset($parts['action']);
        }

        $r = [];
        $r['controller'] = isset($parts['controller']) ? $parts['controller'] : 'index';
        if (isset($parts['area'])) {
            $r['area'] = $parts['area'];
        }

        $r['action'] = isset($parts['action']) ? $parts['action'] : 'index';
        $params = isset($parts['params']) ? $parts['params'] : '';

        unset($parts['area'], $parts['controller'], $parts['action'], $parts['params']);
        if ($params) {
            $parts[0] = $params;
        }
        $r['params'] = $parts;

        return $r;
    }
}