<?php
namespace ManaPHP\Authorization;

use ManaPHP\Component;

/**
 * Class Builder
 * @package ManaPHP\Authorization
 */
class AclBuilder extends Component implements AclBuilderInterface
{
    /**
     * @return array
     */
    public function getControllers()
    {
        $controllers = [];

        foreach (glob($this->alias->resolve('@app/Areas/*/Controllers/*Controller.php')) as $item) {
            $controller = str_replace($this->alias->resolve('@app'), $this->alias->resolveNS('@ns.app'), $item);
            $controllers[] = str_replace('/', '\\', substr($controller, 0, -4));
        }

        foreach (glob($this->alias->resolve('@app/Controllers/*Controller.php')) as $item) {
            $controllers[] = $this->alias->resolveNS('@ns.app\\Controllers\\' . basename($item, '.php'));
        }

        return $controllers;
    }

    /**
     * @param string $controller
     *
     * @return array
     */
    public function getActions($controller)    {
        $actions = [];
        foreach (get_class_methods($controller) as $method) {
            if (preg_match('#^(.*)Action$#', $method, $match)) {
                $actions[] = $match[1];
            }
        }

        return $actions;
    }
}