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
     * @var array
     */
    protected $_controllers;

    /**
     * @return array
     */
    public function getControllers()
    {
        if ($this->_controllers === null) {
            $controllers = [];

            foreach ($this->filesystem->glob('@app/Controllers/*Controller.php') as $item) {
                $controller = str_replace($this->alias->resolve('@app'), $this->alias->resolveNS('@ns.app'), $item);
                $controllers[] = str_replace('/', '\\', substr($controller, 0, -4));
            }

            foreach ($this->filesystem->glob('@app/Areas/*/Controllers/*Controller.php') as $item) {
                $controller = str_replace($this->alias->resolve('@app'), $this->alias->resolveNS('@ns.app'), $item);
                $controllers[] = str_replace('/', '\\', substr($controller, 0, -4));
            }

            $this->_controllers = $controllers;
        }

        return $this->_controllers;
    }

    /**
     * @param string $controller
     *
     * @return array
     */
    public function getActions($controller)
    {
        $actions = [];
        foreach (get_class_methods($controller) as $method) {
            if ($method[0] === '_' || !preg_match('#^(.*)Action$#', $method, $match)) {
                continue;
            }

            $actions[] = $match[1];
        }

        return $actions;
    }
}