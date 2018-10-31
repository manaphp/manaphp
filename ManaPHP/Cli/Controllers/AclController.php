<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Authorization;
use ManaPHP\Cli\Controller;

class AclController extends Controller
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
     * @param \ManaPHP\Controller $controller
     *
     * @return array
     */
    public function getActions($controller)
    {
        $actions = [];
        foreach (get_class_methods($controller) as $method) {
            if (preg_match('#^(.*)Action$#', $method, $match)) {
                $actions[] = $match[1];
            }
        }

        return $actions;
    }

    /**
     * @param string $role
     */
    public function listCommand($role = '')
    {
        $authorization = new Authorization();
        foreach ($this->getControllers() as $controller) {
            /**
             * @var \ManaPHP\Controller $controllerInstance
             */
            $controllerInstance = new $controller;
            $acl = $controllerInstance->getAcl();
            if ($role) {
                $actions = [];
                foreach ($this->getActions($controller) as $action) {
                    if ($authorization->isAllowRoleAction($acl, $role, $action)) {
                        $actions[] = $action;
                    }
                }

                $this->console->writeLn($controller . ': ' . implode(',', $actions));
            } else {
                $this->console->writeLn($controller . ': ' . json_encode($acl));
            }
        }
    }
}