<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

use function basename;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function str_contains;
use function strpos;
use function substr;
use function substr_count;

class PathsNormalizer implements PathsNormalizerInterface
{
    public function normalize(string|array $paths): array
    {
        $routePaths = [];

        if (is_string($paths)) {
            if (($pos = strpos($paths, '::')) !== false) {
                $routePaths['controller'] = substr($paths, 0, $pos);
                $routePaths['action'] = substr($paths, $pos + 2);
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
}