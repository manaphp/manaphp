<?php
namespace ManaPHP\Authorization\Rbac;

use ManaPHP\Authorization\Rbac\PermissionBuilder\Exception as PermissionBuilderException;
use ManaPHP\Component;

/**
 * Class PermissionBuilder
 *
 * @package ManaPHP\Authorization\Rbac
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

        $permissions = [];
        foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            if (preg_match('#^(.*)(Action)$#i', $methodName, $match) !== 1) {
                continue;
            }

            $action = $match[1];

            if ($match[2] !== 'Action') {
                throw new PermissionBuilderException('`:action` action of `:controller` is not suffix with `Action`'/**m05bcf1d580ad9945f*/,
                    ['controller' => $rc->getName(), 'action' => $methodName]);
            }

            if (!$method->isPublic()) {
                throw new PermissionBuilderException('`:action` action of `:controller` does not have public visibility.'/**m096584b24a62a55aa*/,
                    ['controller' => $rc->getName(), 'action' => $methodName]);
            }

            if (preg_match('#^[^/]*/([^/]*)/Controllers/(.*)Controller$#', str_replace('\\', '/', $controller), $match) !== 1) {
                throw new PermissionBuilderException('class name is not good: :controller'/**m0356156d8fc74b80f*/, ['controller' => $rc->getName()]);
            }

            $permissions[] = [
                'module' => $match[1],
                'controller' => $match[2],
                'action' => $action,
                'description' => $match[1] . '::' . $match[2] . '::' . $action
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

        foreach ($this->filesystem->glob('@app/' . $module . '/Controllers/*.php') as $file) {
            $file = str_replace(dirname($app) . '/', '', $file);
            $controller = str_replace('/', '\\', pathinfo($file, PATHINFO_DIRNAME) . '\\' . basename($file, '.php'));
            $permissions = array_merge($permissions, $this->getControllerPermissions($controller));
        }

        return $permissions;
    }
}