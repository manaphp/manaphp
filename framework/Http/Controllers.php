<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Alias\Path;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use function get_class_methods;
use function preg_match;
use function str_replace;
use function str_starts_with;
use function substr;

class Controllers implements ControllersInterface
{
    protected ?array $controllers = null;

    public function getControllers(): array
    {
        if ($this->controllers === null) {
            $controllers = [];

            foreach (LocalFS::glob('@app/Controllers/?*Controller.php') as $item) {
                $controller = str_replace(Path::resolve('@app'), 'App', $item);
                $controllers[] = str_replace('/', '\\', substr($controller, 0, -4));
            }

            foreach (LocalFS::glob('@app/Areas/*/Controllers/?*Controller.php') as $item) {
                $controller = str_replace(Path::resolve('@app'), 'App', $item);
                $controllers[] = str_replace('/', '\\', substr($controller, 0, -4));
            }

            $this->controllers = $controllers;
        }

        return $this->controllers;
    }

    public function getActions($controller): array
    {
        $actions = [];
        foreach (get_class_methods($controller) as $method) {
            if (str_starts_with($method, '_') || !preg_match('#^(.*)Action$#', $method, $match)) {
                continue;
            }

            $actions[] = $match[1];
        }

        return $actions;
    }

    public function getPath(string $controller, string $action): string
    {
        $controllerPath = str_replace('\\', '/', $controller);
        $action = Str::snakelize($action);

        if (preg_match('#Areas/([^/]+)/Controllers/(.*)Controller$#', $controllerPath, $match)) {
            $area = Str::snakelize($match[1]);
            $controller = Str::snakelize($match[2]);

            if ($action === 'index') {
                if ($controller === 'index') {
                    return $area === 'index' ? '/' : "/$area";
                } else {
                    return "/$area/$controller";
                }
            } else {
                return "/$area/$controller/$action";
            }
        } elseif (preg_match('#/Controllers/(.*)Controller#', $controllerPath, $match)) {
            $controller = Str::snakelize($match[1]);

            if ($action === 'index') {
                return $controller === 'index' ? '/' : "/$controller";
            } else {
                return "/$controller/$action";
            }
        } else {
            throw new MisuseException('Invalid controller: "{controllerPath}".', ['controllerPath' => $controllerPath]);
        }
    }
}
