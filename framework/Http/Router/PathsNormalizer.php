<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

use function basename;
use function explode;
use function is_string;
use function preg_match;
use function str_contains;
use function substr_count;

class PathsNormalizer implements PathsNormalizerInterface
{
    public function normalize(string|array $paths): array
    {
        $routePaths = [];

        if (is_string($paths)) {
            if (str_contains($paths, '::')) {
                $paths = explode('::', $paths, 2);
            } else {
                $paths = [$paths, null];
            }
        }
        list($controller, $action) = is_string($paths) ? explode('::', $paths, 2) : $paths;

        $routePaths['controller'] = $controller;
        $routePaths['action'] = $action === null ? null : basename($action, 'Action');

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