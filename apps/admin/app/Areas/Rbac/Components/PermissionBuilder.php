<?php
namespace App\Admin\Areas\Rbac\Components;

use ManaPHP\Component;
use App\Admin\Areas\Rbac\Components\PermissionBuilder\Exception as PermissionBuilderException;

class PermissionBuilder extends Component
{
    /**
     * @return array
     */
    public function getAreas()
    {
        if (!$this->filesystem->dirExists('@app/Areas/')) {
            return [];
        }

        $areas = [];
        foreach ($this->filesystem->glob('@app/Areas/*', GLOB_ONLYDIR) as $dir) {
            if ($this->filesystem->dirExists($dir . '/Controllers')) {
                $areas[] = basename($dir);
            }
        }

        return $areas;
    }

    /**
     * @param string $area
     *
     * @return array
     */
    public function getControllers($area)
    {
        $controllers = [];
        if ($area) {
            foreach (glob($this->alias->resolve("@app/Areas/$area/Controllers/*Controller.php")) as $item) {
                $controllers[] = $this->alias->resolveNS("@ns.app\\Areas\\$area\\Controllers\\" . basename($item, '.php'));
            }
        } else {
            foreach (glob($this->alias->resolve('@app/Controllers/*Controller.php')) as $item) {
                $controllers[] = $this->alias->resolveNS('@ns.app\\Controllers\\' . basename($item, '.php'));
            }
        }

        return $controllers;
    }

    /**
     * @param string $controllerName
     *
     * @return array
     */
    public function getActions($controllerName)
    {
        $actions = [];
        $rc = new \ReflectionClass($controllerName);

        foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            if (preg_match('#^(.*)(Action)$#i', $methodName, $match) !== 1) {
                continue;
            }

            $actionName = $match[1];

            if ($match[2] !== 'Action') {
                throw new PermissionBuilderException([
                    '`:action` action of `:controller` is not suffix with `Action`'/**m05bcf1d580ad9945f*/,
                    'controller' => $rc->getName(),
                    'action' => $methodName
                ]);
            }

            if (!$method->isPublic()) {
                throw new PermissionBuilderException([
                    '`:action` action of `:controller` does not have public visibility.'/**m096584b24a62a55aa*/,
                    'controller' => $rc->getName(),
                    'action' => $methodName
                ]);
            }

            $actions[$actionName] = $actionName;
        }

        return $actions;
    }
}