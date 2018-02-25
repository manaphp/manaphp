<?php
namespace ManaPHP\Authorization\Rbac;

use ManaPHP\Authorization\Rbac\PermissionBuilder\Exception as PermissionBuilderException;
use ManaPHP\Component;

/**
 * Class ManaPHP\Authorization\Rbac\PermissionBuilder
 *
 * @package rbac
 *
 * @property \ManaPHP\AliasInterface $alias
 */
class PermissionBuilder extends Component
{
    /**
     * @return array
     */
    public function getModules()
    {
        if ($this->filesystem->dirExists('@app/Controllers')) {
            return [''];
        } else {
            $modules = [];
            foreach ($this->filesystem->glob('@app/*', GLOB_ONLYDIR) as $dir) {
                if ($this->filesystem->dirExists($dir . '/Controllers')) {
                    $modules[] = basename($dir);
                }
            }

            return $modules;
        }
    }

    /**
     * @param string $moduleName
     *
     * @return array
     */
    public function getControllers($moduleName)
    {
        $controllers = [];
        if ($moduleName) {
            foreach (glob($this->alias->resolve("@app/$moduleName/Controllers/*Controller.php")) as $item) {
                $controllers[] = $this->alias->resolveNS("@ns.app\\$moduleName\\Controllers\\" . basename($item, '.php'));
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
     * @throws \ManaPHP\Authorization\Rbac\PermissionBuilder\Exception
     */
    public function getActions($controllerName)
    {
        $actions = [];
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        $rc = new \ReflectionClass($controllerName);

        foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            if (preg_match('#^(.*)(Action)$#i', $methodName, $match) !== 1) {
                continue;
            }

            $actionName = $match[1];

            if ($match[2] !== 'Action') {
                throw new PermissionBuilderException(['`:action` action of `:controller` is not suffix with `Action`'/**m05bcf1d580ad9945f*/,
                    'controller' => $rc->getName(), 'action' => $methodName]);
            }

            if (!$method->isPublic()) {
                throw new PermissionBuilderException(['`:action` action of `:controller` does not have public visibility.'/**m096584b24a62a55aa*/,
                    'controller' => $rc->getName(), 'action' => $methodName]);
            }

            $actions[$actionName] = $actionName;
        }

        return $actions;
    }
}