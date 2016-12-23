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
     * @param string $controller
     *
     * @return array
     * @throws \ManaPHP\Authorization\Rbac\PermissionBuilder\Exception
     */
    public function getControllerPermissions($controller)
    {
        $rc = new \ReflectionClass($controller);

        if (preg_match('#^[^/]*/([^/]*)/Controllers/(.*)Controller$#', str_replace('\\', '/', $controller), $match) !== 1) {
            return [];
        }
        $moduleName = $match[1];
        $controllerName = $match[2];

        $permissions = [];
        foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            if (preg_match('#^(.*)(Action)$#i', $methodName, $match) !== 1) {
                continue;
            }

            $actionName = $match[1];

            if ($match[2] !== 'Action') {
                throw new PermissionBuilderException('`:action` action of `:controller` is not suffix with `Action`'/**m05bcf1d580ad9945f*/,
                    ['controller' => $rc->getName(), 'action' => $methodName]);
            }

            if (!$method->isPublic()) {
                throw new PermissionBuilderException('`:action` action of `:controller` does not have public visibility.'/**m096584b24a62a55aa*/,
                    ['controller' => $rc->getName(), 'action' => $methodName]);
            }

            $permissions[] = [
                'module' => $moduleName,
                'controller' => $controllerName,
                'action' => $actionName,
                'description' => $moduleName . '::' . $controllerName . '::' . $actionName
            ];
        }

        return $permissions;
    }

    /**
     * @param string $module
     *
     * @return array
     * @throws \ManaPHP\Authorization\Rbac\PermissionBuilder\Exception
     */
    public function getModulePermissions($module)
    {
        $app = $this->alias->get('@app');

        $permissions = [];

        if (!$this->filesystem->dirExists('@app/' . $module)) {
            throw new PermissionBuilderException('`:module_dir` module directory is not exists.', ['module_dir' => $this->alias->resolve('@app/' . $module)]);
        }

        foreach ($this->filesystem->glob('@app/' . $module . '/Controllers/*.php') as $file) {
            $file = str_replace(dirname($app) . '/', '', $file);
            $controller = str_replace('/', '\\', pathinfo($file, PATHINFO_DIRNAME) . '\\' . basename($file, '.php'));
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $permissions = array_merge($permissions, $this->getControllerPermissions($controller));
        }

        return $permissions;
    }
}