<?php

namespace ManaPHP\Mvc\Router;

use ManaPHP\Utility\Text;

/**
 * ManaPHP\Mvc\Router\Route
 *
 * This class represents every route added to the router
 *
 * NOTE_PHP:
 *    Hostname Constraints has been removed by PHP implementation
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
    protected $_httpMethod;

    /**
     * \ManaPHP\Mvc\Router\Route constructor
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string       $httpMethod
     *
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public function __construct($pattern, $paths = null, $httpMethod = null)
    {
        $this->_pattern = $pattern;
        $this->_compiledPattern = $this->_compilePattern($pattern);
        $this->_paths = self::getRoutePaths($paths);
        $this->_httpMethod = $httpMethod;
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
        if (Text::contains($pattern, ':')) {
            $tr = [
                '/:module' => '/{module:[a-z\d_-]+}',
                '/:controller' => '/{controller:[a-z\d_-]+}',
                '/:action' => '/{action:[a-z\d_-]+}',
                '/:params' => '/{params:.+}',
                '/:int' => '/(\d+)',
            ];
            $pattern = strtr($pattern, $tr);
        }

        if (Text::contains($pattern, '{')) {
            $pattern = $this->_extractNamedParams($pattern);
        }

        if (Text::contains($pattern, '(') || Text::contains($pattern, '[')) {
            return '#^' . $pattern . '$#i';
        } else {
            return $pattern;
        }
    }

    /**
     * Extracts parameters from a string
     *
     * @param string $pattern
     *
     * @return string
     */
    protected function _extractNamedParams($pattern)
    {
        if (!Text::contains($pattern, '{')) {
            return $pattern;
        }

        $left_token = '@_@';
        $right_token = '!_!';
        $need_restore_token = false;

        if (preg_match('#{\d#', $pattern) === 1
            && !Text::contains($pattern, $left_token)
            && !Text::contains($pattern, $right_token)
        ) {
            $need_restore_token = true;
            $pattern = preg_replace('#{(\d+,?\d*)}#', $left_token . '\1' . $right_token, $pattern);
        }

        $matches = null;
        if (preg_match_all('#{([A-Z].*)}#Ui', $pattern, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {

                if (!Text::contains($match[0], ':')) {
                    $to = '(?<' . $match[1] . '>[\w-]+)';
                    $pattern = str_replace($match[0], $to, $pattern);
                } else {
                    $parts = explode(':', $match[1]);
                    $to = '(?<' . $parts[0] . '>' . $parts[1] . ')';
                    $pattern = str_replace($match[0], $to, $pattern);
                }
            }
        }

        if ($need_restore_token) {
            $from = [$left_token, $right_token];
            $to = ['{', '}'];
            $pattern = str_replace($from, $to, $pattern);
        }

        return $pattern;
    }

    /**
     * Returns routePaths
     *
     * @param string|array $paths
     *
     * @return array
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public static function getRoutePaths($paths = null)
    {
        if ($paths !== null) {
            if (is_array($paths) && isset($paths[0])) {
                $paths = implode('::', $paths);
            }

            if (is_string($paths)) {
                $parts = explode('::', $paths);

                $moduleName = '';
                $actionName = '';

                if (count($parts) === 3) {
                    $moduleName = $parts[0];
                    $controllerName = $parts[1];
                    $actionName = $parts[2];
                } elseif (count($parts) === 2) {
                    $controllerName = $parts[0];
                    $actionName = $parts[1];
                } else {
                    $controllerName = $parts[0];
                }

                $routePaths = [];
                if ($moduleName !== '') {
                    $routePaths['module'] = $moduleName;
                }

                if ($controllerName !== '') {
                    $routePaths['controller'] = $controllerName;
                }

                if ($actionName !== '') {
                    $routePaths['action'] = $actionName;
                }
            } elseif (is_array($paths)) {
                $routePaths = $paths;
            } else {
                throw new Exception('--paths must be a string or array.');
            }

            if (isset($routePaths['controller']) && is_string($routePaths['controller'])) {
                $parts = explode('\\', $routePaths['controller']);
                $routePaths['controller'] = basename(end($parts), 'Controller');
            }
        } else {
            $routePaths = [];
        }

        return $routePaths;
    }

    /**
     * Returns the paths
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->_paths;
    }

    /**
     * @param string $uri
     *
     * @return bool|array
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public function match($uri)
    {
        $matches = [];

        if ($this->_httpMethod !== null && $this->_httpMethod !== $_SERVER['REQUEST_METHOD']) {
            return false;
        }

        if (Text::contains($this->_compiledPattern, '^')) {
            $r = preg_match($this->_compiledPattern, $uri, $matches);
            if ($r === false) {
                throw new Exception('--invalid PCRE: ' . $this->_compiledPattern . ' for ' . $this->_pattern);
            } elseif ($r === 1) {
                return $matches;
            } else {
                return false;
            }
        } else {
            if ($this->_compiledPattern === $uri) {
                return $matches;
            } else {
                return false;
            }
        }
    }
}