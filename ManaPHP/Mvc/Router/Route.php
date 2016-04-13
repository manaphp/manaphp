<?php

namespace ManaPHP\Mvc\Router {

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
         * @var array|null|string
         */
        protected $_httpMethods;

        /**
         * \ManaPHP\Mvc\Router\Route constructor
         *
         * @param string       $pattern
         * @param array        $paths
         * @param array|string $httpMethods
         *
         * @throws \ManaPHP\Mvc\Router\Exception
         */
        public function __construct($pattern, $paths = null, $httpMethods = null)
        {
            $this->_pattern = $pattern;
            $this->_compiledPattern = $this->_compilePattern($pattern);
            $this->_paths = self::getRoutePaths($paths);
            $this->_httpMethods = $httpMethods;
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
                $pattern = strtr($pattern, [
                    '/:module' => '/{module:[a-z\d_-]+}',
                    '/:controller' => '/{controller:[a-z\d_-]+}',
                    '/:action' => '/{action:[a-z\d_-]+}',
                    '/:params' => '/{params:.+}',
                    '/:int' => '/(\d+)',
                ]);
            }

            if (strpos($pattern, '{') !== false) {
                $pattern = $this->_extractNamedParams($pattern);
            }

            if (strpos($pattern, '(') !== false || strpos($pattern, '[') !== false) {
                return '#^' . $pattern . '$#';
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
            if (strpos($pattern, '{') === false) {
                return $pattern;
            }

            $left_token = '@_@';
            $right_token = '!_!';
            $need_restore_token = false;

            if (preg_match('#{\d#', $pattern) === 1
                && strpos($pattern, $left_token) === false
                && strpos($pattern, $right_token) === false
            ) {
                $need_restore_token = true;
                $pattern = preg_replace('#{(\d+,?\d*)}#', $left_token . '\1' . $right_token, $pattern);
            }

            if (preg_match_all('#{([A-Z].*)}#Ui', $pattern, $matches, PREG_SET_ORDER) > 0) {
                foreach ($matches as $match) {

                    if (strpos($match[0], ':') === false) {
                        $pattern = str_replace($match[0], '(?<' . $match[1] . '>[\w-]+)', $pattern);
                    } else {
                        $parts = explode(':', $match[1]);
                        $pattern = str_replace($match[0], '(?<' . $parts[0] . '>' . $parts[1] . ')', $pattern);
                    }
                }
            }

            if ($need_restore_token) {
                $pattern = str_replace([$left_token, $right_token], ['{', '}'], $pattern);
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
                if (is_string($paths)) {
                    $parts = explode('::', $paths);

                    if (count($parts) === 3) {
                        list($moduleName, $controllerName, $actionName) = $parts;
                    } elseif (count($parts) === 2) {
                        list($controllerName, $actionName) = $parts;
                    } else {
                        $controllerName = $parts[0];
                    }

                    $routePaths = [];
                    if (isset($moduleName)) {
                        $routePaths['module'] = $moduleName;
                    }

                    if (isset($controllerName)) {
                        $routePaths['controller'] = $controllerName;
                    }

                    if (isset($actionName)) {
                        $routePaths['action'] = $actionName;
                    }
                } elseif (is_array($paths)) {
                    $routePaths = $paths;
                } else {
                    throw new Exception('--paths must be a string or array.');
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
         * @param string     $handle_uri
         * @param array|null $matches
         *
         * @return bool
         * @throws \ManaPHP\Mvc\Router\Exception
         */
        public function isMatched($handle_uri, &$matches)
        {
            if ($this->_httpMethods !== null) {
                if (is_string($this->_httpMethods)) {
                    if ($this->_httpMethods !== $_SERVER['REQUEST_METHOD']) {
                        return false;
                    }
                } else {
                    if (!in_array($_SERVER['REQUEST_METHOD'], $this->_httpMethods, true)) {
                        return false;
                    }
                }
            }

            if (strpos($this->_compiledPattern, '^') !== false) {
                $r = preg_match($this->_compiledPattern, $handle_uri, $matches);
                if ($r === false) {
                    throw new Exception('--invalid PCRE: ' . $this->_compiledPattern . ' for ' . $this->_pattern);
                } elseif ($r === 1) {
                    return true;
                } else {
                    return false;
                }
            } else {
                if ($this->_compiledPattern === $handle_uri) {
                    $matches = [];

                    return true;
                } else {
                    return false;
                }
            }
        }
    }
}
